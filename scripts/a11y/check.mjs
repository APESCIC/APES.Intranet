// Accessibility check: axe-core (WCAG 2.2 A and AA) on the main pages, plus real
// keyboard checks. Fails on any serious or critical axe issue or keyboard
// failure. Needs a running app with the QA role switcher on.
//
//   docker compose exec -e BASE_URL=http://intranet.apes.test:8080 node npm run test:a11y
//
// A11Y_BROWSER_CHANNEL uses an installed browser (msedge, chrome) instead of
// Playwright's own Chromium.

import AxeBuilder from '@axe-core/playwright';
import { chromium } from 'playwright';

const baseUrl = (process.env.BASE_URL ?? 'http://127.0.0.1:8000').replace(/\/$/, '');
const channel = process.env.A11Y_BROWSER_CHANNEL || undefined;
const blocking = new Set(['serious', 'critical']);
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];

const results = [];
const keyboardFailures = [];

function expectKeyboard(condition, message) {
    console.log(`${condition ? 'PASS' : 'FAIL'}  keyboard: ${message}`);
    if (!condition) {
        keyboardFailures.push(message);
    }
}

async function focused(page) {
    return page.evaluate(() => {
        const element = document.activeElement;
        const style = element ? getComputedStyle(element) : null;

        return {
            label: element?.getAttribute('aria-label') ?? element?.textContent?.trim().replace(/\s+/g, ' ') ?? '',
            id: element?.id ?? '',
            insideDrawer: Boolean(element?.closest('[data-workspace-drawer-panel]')),
            outline: style ? style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) >= 2 : false,
            top: element?.getBoundingClientRect().top ?? -1,
        };
    });
}

async function keyboardChecks(desktop, mobile) {
    await desktop.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await desktop.keyboard.press('Tab');
    let state = await focused(desktop);
    expectKeyboard(state.label === 'Skip to main content' && state.top >= 0, 'first Tab shows the skip link');

    await desktop.keyboard.press('Enter');
    state = await focused(desktop);
    expectKeyboard(state.id === 'main-content', 'skip link moves focus to the main content');

    // Staff has no Admin menu switcher, so the first rail tab after the skip
    // link reaches the Dashboard item. Admins see Normal/Admin controls first.
    await signInAs(desktop, 'Staff');
    await desktop.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    state = await focused(desktop);
    expectKeyboard(state.label.startsWith('Dashboard') && state.outline, 'menu links are reached in order with a visible focus ring');

    await mobile.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await mobile.keyboard.press('Tab');
    await mobile.keyboard.press('Tab');
    state = await focused(mobile);
    expectKeyboard(state.label === 'Open menu', 'menu button is reached on mobile');

    await mobile.keyboard.press('Enter');
    await mobile.getByRole('dialog', { name: 'Main menu' }).waitFor();
    state = await focused(mobile);
    expectKeyboard(state.label === 'Close menu', 'opening the menu moves focus to Close menu');

    let trapped = true;
    for (let i = 0; i < 25; i++) {
        await mobile.keyboard.press('Tab');
        trapped &&= (await focused(mobile)).insideDrawer;
    }
    expectKeyboard(trapped, 'Tab stays inside the open menu');

    await mobile.keyboard.press('Escape');
    state = await focused(mobile);
    expectKeyboard(state.label === 'Open menu', 'Escape closes the menu and returns focus to Open menu');
}

async function audit(page, label) {
    const { violations, passes, incomplete } = await new AxeBuilder({ page }).withTags(tags).analyze();
    results.push({ label, url: page.url(), violations, passes, incomplete });
}

async function visit(page, path, label, expectedStatus = 200) {
    const response = await page.goto(baseUrl + path, { waitUntil: 'networkidle' });
    if (response?.status() !== expectedStatus) {
        throw new Error(`${path} returned ${response?.status()}, expected ${expectedStatus}`);
    }
    await audit(page, label);
}

async function signInAs(page, role) {
    await page.goto(`${baseUrl}/qa`, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: role, exact: true }).click();
    await page.waitForURL(`${baseUrl}/dashboard`);
}

const browser = await chromium.launch({ channel });

try {
    const desktop = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
    await visit(desktop, '/login', 'Sign in');
    await visit(desktop, '/privacy', 'Privacy notice');
    await visit(desktop, '/no-such-page', 'Error 404', 404);

    await signInAs(desktop, 'Staff');
    await visit(desktop, '/admin/roles', 'Error 403', 403);

    await signInAs(desktop, 'Admin');
    await visit(desktop, '/dashboard', 'Dashboard (desktop)');
    await visit(desktop, '/admin/roles', 'Roles');
    await visit(desktop, '/admin/audit', 'Audit log');

    const mobile = await (await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })).newPage();
    await signInAs(mobile, 'Volunteer');
    await visit(mobile, '/dashboard', 'Dashboard (mobile)');
    await mobile.getByRole('button', { name: 'Open menu' }).click();
    await mobile.getByRole('dialog', { name: 'Main menu' }).waitFor();
    await mobile.waitForTimeout(300);
    await audit(mobile, 'Menu drawer (mobile)');

    await keyboardChecks(desktop, mobile);
} finally {
    await browser.close();
}

let failures = 0;

for (const { label, url, violations, passes, incomplete } of results) {
    const serious = violations.filter((v) => blocking.has(v.impact));
    const other = violations.filter((v) => !blocking.has(v.impact));
    const contrast = passes.some((p) => p.id === 'color-contrast') ? 'contrast ok' : 'contrast not confirmed';
    console.log(`${serious.length === 0 ? 'PASS' : 'FAIL'}  ${label}  ${url}  (${passes.length} rules passed, ${contrast})`);

    if (incomplete.length > 0) {
        console.log(`  needs a manual check: ${incomplete.map((i) => i.id).join(', ')}`);
    }

    for (const violation of [...serious, ...other]) {
        console.log(`  [${violation.impact}] ${violation.id}: ${violation.help}`);
        for (const node of violation.nodes.slice(0, 3)) {
            console.log(`      ${node.target.join(' ')}`);
        }
    }

    failures += serious.length;
}

console.log(`\n${results.length} pages checked, ${failures} serious or critical issues, ${keyboardFailures.length} keyboard failures.`);
process.exit(failures === 0 && keyboardFailures.length === 0 ? 0 : 1);
