// Accessibility check: axe-core (WCAG 2.2 A and AA) on the main pages, plus real
// keyboard checks. Fails on any serious or critical axe issue or keyboard
// failure. Needs a running app with QA sign-in on (QA_LOGIN_ENABLED=true).
//
//   A11Y_BROWSER_CHANNEL=msedge npm run test:a11y
//
// BASE_URL defaults to the Herd preview, https://apes-intranet.test. If the site
// redirects to https, the check follows it.
//
// A11Y_BROWSER_CHANNEL uses an installed browser (msedge, chrome) instead of
// Playwright's own Chromium.

import AxeBuilder from '@axe-core/playwright';
import { chromium } from 'playwright';

let baseUrl = (process.env.BASE_URL ?? 'https://apes-intranet.test').replace(/\/$/, '');
const channel = process.env.A11Y_BROWSER_CHANNEL || undefined;
const blocking = new Set(['serious', 'critical']);
const tags = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa'];
// Herd injects its "Edit with Herd" badge into every local page; it is not part of the app.
const excluded = ['#herd-ai-host'];

const results = [];
const keyboardFailures = [];

function expectKeyboard(condition, message, kind = 'keyboard') {
    console.log(`${condition ? 'PASS' : 'FAIL'}  ${kind}: ${message}`);
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

    // Admins reach the Staff and Admin switch after the brand (and the
    // notification bell, when it is present in the rail).
    await desktop.goto(`${baseUrl}/admin/roles`, { waitUntil: 'networkidle' });
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    state = await focused(desktop);
    if (state.label.startsWith('Notifications')) {
        await desktop.keyboard.press('Tab');
        state = await focused(desktop);
    }
    expectKeyboard(state.label === 'Staff' && state.outline, 'admins reach the Staff switch after the brand, with a visible focus ring');

    await desktop.keyboard.press('Tab');
    state = await focused(desktop);
    const adminCurrent = await desktop.evaluate(() => document.activeElement?.getAttribute('aria-current'));
    expectKeyboard(state.label === 'Admin' && adminCurrent === 'true', 'the Admin switch comes next and is marked as the current panel');

    await desktop.keyboard.press('Shift+Tab');
    await desktop.keyboard.press('Enter');
    await desktop.waitForURL(`${baseUrl}/dashboard`);
    const staffCurrent = await desktop.getByRole('navigation', { name: 'Switch panel' }).getByRole('link', { name: 'Staff' }).getAttribute('aria-current');
    expectKeyboard(staffCurrent === 'true', 'the Staff switch opens the dashboard with Staff marked current');

    // Staff has no panel switch, so after skip + brand (+ notification bell)
    // the next rail tab reaches the Dashboard item.
    await signInAs(desktop, 'Staff');
    await desktop.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Tab');
    state = await focused(desktop);
    if (state.label.startsWith('Notifications')) {
        await desktop.keyboard.press('Tab');
        state = await focused(desktop);
    }
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

    // The footer version pill sits just before the Privacy notice link.
    await desktop.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await desktop.getByRole('link', { name: 'Privacy notice' }).focus();
    await desktop.keyboard.press('Shift+Tab');
    state = await focused(desktop);
    expectKeyboard(state.label.startsWith('View the intranet change log') && state.outline, 'footer version pill is reached by keyboard with a visible focus ring');

    await desktop.goto(`${baseUrl}/change-log`, { waitUntil: 'networkidle' });
    await desktop.getByRole('navigation', { name: 'Filter by type of change' }).getByRole('link', { name: 'All changes' }).focus();
    await desktop.keyboard.press('Tab');
    await desktop.keyboard.press('Enter');
    await desktop.waitForURL(/[?&]type=added/);
    const current = await desktop.getByRole('link', { name: 'Added', exact: true }).getAttribute('aria-current');
    expectKeyboard(current === 'page', 'change log filter works from the keyboard and marks the chosen type');
}

// The switcher moves focus to the current role on its toggle event, a moment
// after it becomes visible, so wait for that before reading or moving focus.
async function waitForFocusInSwitcher(page) {
    await page.waitForFunction(() => Boolean(document.activeElement?.closest('[data-qa-flyout]')), null, { timeout: 5000 })
        .catch(() => {});
}

async function qaSwitcherChecks(desktop, narrow) {
    await signInAs(desktop, 'Staff');
    await desktop.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    const pill = desktop.locator('[data-workspace-rail-dock] [data-qa-pill]');
    const pillBox = await pill.boundingBox();
    expectKeyboard((await pill.textContent()).trim() === 'QA: Staff' && pillBox !== null && pillBox.height >= 44, 'the amber QA pill shows the current profile with a 44px target', 'layout');

    await pill.focus();
    await desktop.keyboard.press('Enter');
    const flyout = desktop.locator('[data-workspace-rail-dock] [data-qa-flyout]');
    await flyout.waitFor();
    await waitForFocusInSwitcher(desktop);
    let state = await focused(desktop);
    expectKeyboard(state.label === 'Staff (current)' && (await pill.getAttribute('aria-expanded')) === 'true', 'Enter on the QA pill opens the switcher on the current profile');
    await audit(desktop, 'QA switcher (desktop)');

    await desktop.keyboard.press('Escape');
    state = await focused(desktop);
    expectKeyboard(!(await flyout.isVisible()) && state.label === 'QA: Staff', 'Escape closes the QA switcher and returns focus to the pill');

    await pill.focus();
    await desktop.keyboard.press('Enter');
    await waitForFocusInSwitcher(desktop);
    await flyout.getByRole('button', { name: 'Admin', exact: true }).focus();
    await Promise.all([desktop.waitForResponse((response) => response.url().endsWith('/qa/login')), desktop.keyboard.press('Enter')]);
    await desktop.waitForLoadState('networkidle');
    expectKeyboard(desktop.url() === `${baseUrl}/dashboard` && (await pill.textContent()).trim() === 'QA: Admin', 'switching role from the keyboard stays on a page the new role may open');

    await desktop.goto(`${baseUrl}/admin/roles`, { waitUntil: 'networkidle' });
    await pill.click();
    await Promise.all([
        desktop.waitForResponse((response) => response.url().endsWith('/qa/login')),
        flyout.getByRole('button', { name: 'Staff', exact: true }).click(),
    ]);
    await desktop.waitForURL(`${baseUrl}/dashboard`);
    await desktop.waitForLoadState('networkidle');
    expectKeyboard((await pill.textContent()).trim() === 'QA: Staff', 'switching to a role that may not open the page lands on the dashboard');

    await narrow.goto(`${baseUrl}/dashboard`, { waitUntil: 'networkidle' });
    await narrow.getByRole('button', { name: 'Open menu' }).click();
    const drawer = narrow.getByRole('dialog', { name: 'Main menu' });
    await drawer.waitFor();
    await narrow.waitForTimeout(300);
    await drawer.locator('[data-qa-pill]').focus();
    await narrow.keyboard.press('Enter');
    const drawerFlyout = drawer.locator('[data-qa-flyout]');
    await drawerFlyout.waitFor();
    await waitForFocusInSwitcher(narrow);
    const fits = await drawerFlyout.evaluate((element) => element.getBoundingClientRect().right <= window.innerWidth
        && [...element.querySelectorAll('button')].every((button) => button.getBoundingClientRect().height >= 44));
    expectKeyboard(fits && (await focused(narrow)).insideDrawer, 'the QA switcher fits the 320px menu with 44px targets', 'layout');

    await narrow.keyboard.press('Escape');
    state = await focused(narrow);
    expectKeyboard(!(await drawerFlyout.isVisible()) && state.insideDrawer && state.label.startsWith('QA: '), 'Escape closes the QA switcher but leaves the 320px menu open');

    await narrow.keyboard.press('Escape');
    expectKeyboard((await focused(narrow)).label === 'Open menu', 'a second Escape closes the 320px menu');
}

async function narrowScreenChecks(narrow) {
    await signInAs(narrow, 'Staff');
    for (const path of ['/dashboard', '/change-log']) {
        await narrow.goto(baseUrl + path, { waitUntil: 'networkidle' });
        const fits = await narrow.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
        const pill = await narrow.locator('[data-app-version]').boundingBox();
        expectKeyboard(fits && pill !== null && pill.height >= 44 && pill.width >= 44, `${path} fits 320px with a 44px version pill`, 'layout');
    }

    await signInAs(narrow, 'Admin');
    await narrow.goto(`${baseUrl}/admin`, { waitUntil: 'networkidle' });
    await narrow.getByRole('button', { name: 'Open menu' }).click();
    const drawer = narrow.getByRole('dialog', { name: 'Main menu' });
    await drawer.waitFor();
    await narrow.waitForTimeout(300);
    const links = drawer.getByRole('navigation', { name: 'Switch panel' }).getByRole('link');
    const boxes = await Promise.all([0, 1].map((i) => links.nth(i).boundingBox()));
    const fits = await narrow.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
    expectKeyboard(fits && boxes.every((box) => box !== null && box.height >= 44 && box.width >= 44), 'the Staff and Admin switch fits the 320px menu with 44px targets', 'layout');

    let trapped = true;
    let reachedSwitch = false;
    for (let i = 0; i < 15; i++) {
        await narrow.keyboard.press('Tab');
        const state = await focused(narrow);
        trapped &&= state.insideDrawer;
        reachedSwitch ||= state.label === 'Admin';
    }
    expectKeyboard(trapped && reachedSwitch, 'Tab reaches the switch and stays inside the 320px menu');

    await narrow.keyboard.press('Escape');
    expectKeyboard((await focused(narrow)).label === 'Open menu', 'Escape closes the 320px menu and returns focus to Open menu');
}

async function audit(page, label) {
    const { violations, passes, incomplete } = await new AxeBuilder({ page }).withTags(tags).exclude(excluded).analyze();
    results.push({ label, url: page.url(), violations, passes, incomplete });
}

async function visit(page, path, label, expectedStatus = 200) {
    const response = await page.goto(baseUrl + path, { waitUntil: 'networkidle' });
    if (response?.status() !== expectedStatus) {
        throw new Error(`${path} returned ${response?.status()}, expected ${expectedStatus}`);
    }
    await audit(page, label);
}

// Opens the first ticket in a list. Without demo data (as in CI) it raises one
// through the form first; the first topic belongs to the IT queue.
async function visitFirstTicket(page, listPath, label) {
    await page.goto(baseUrl + listPath, { waitUntil: 'networkidle' });
    const link = page.locator('[data-ticket] a').first();
    if ((await link.count()) > 0) {
        await visit(page, new URL(await link.getAttribute('href')).pathname, label);
        return;
    }

    await page.goto(`${baseUrl}/help-desk/tickets/create`, { waitUntil: 'networkidle' });
    await page.locator('#category_id').selectOption({ index: 1 });
    await page.locator('#subject').fill('Accessibility check ticket');
    await page.locator('#body-surface [contenteditable]').fill('Raised by the accessibility check.');
    await Promise.all([
        page.waitForURL(/\/help-desk\/tickets\/\d+$/),
        page.getByRole('button', { name: 'Raise ticket' }).click(),
    ]);
    await page.waitForLoadState('networkidle');
    await audit(page, label);
}

// Uses the Developer sign-in panel on /login, or the /qa fallback when already
// signed in (signed-in visits to /login go to the dashboard).
async function signInAs(page, role) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'networkidle' });
    if (!page.url().endsWith('/login')) {
        await page.goto(`${baseUrl}/qa`, { waitUntil: 'networkidle' });
    }
    await page.locator('#developer-signin').getByRole('button', { name: role, exact: true }).click();
    await page.waitForURL(`${baseUrl}/dashboard`);
}

const browser = await chromium.launch({ channel });

try {
    const desktop = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
    await visit(desktop, '/login', 'Sign in');
    // A secured Herd site redirects http to https; carry on at the address it chose.
    baseUrl = new URL(desktop.url()).origin;
    await visit(desktop, '/privacy', 'Privacy notice');
    await visit(desktop, '/no-such-page', 'Error 404', 404);

    await signInAs(desktop, 'Staff');
    await visit(desktop, '/admin/roles', 'Error 403', 403);

    await signInAs(desktop, 'Admin');
    await visit(desktop, '/dashboard', 'Dashboard (desktop)');
    await visit(desktop, '/notifications/preferences', 'Notification preferences');
    await visit(desktop, '/notifications', 'Notification inbox');
    await visit(desktop, '/admin', 'Admin overview');
    await visit(desktop, '/admin/roles', 'Roles');
    await visit(desktop, '/admin/modules', 'Modules');
    await visit(desktop, '/admin/audit', 'Audit log');
    await visit(desktop, '/admin/push-delivery', 'Push delivery');
    await visit(desktop, '/change-log', 'Change log (desktop)');
    await visit(desktop, '/change-log?type=fixed', 'Change log filtered (desktop)');
    await visit(desktop, '/admin/operations/sites', 'Operations sites');
    await visit(desktop, '/admin/operations/forms', 'Form builder');
    await visit(desktop, '/admin/help-desk/topics', 'Help desk topics');
    await visit(desktop, '/help-desk/queues/it', 'IT queue');
    await visit(desktop, '/help-desk/queues/management?view=all', 'Management queue');
    await visit(desktop, '/help-desk/reports', 'Help desk reports');
    await visitFirstTicket(desktop, '/help-desk/queues/it', 'Ticket (queue member)');
    await visit(desktop, '/learning', 'My learning (desktop)');
    await visit(desktop, '/learning/courses', 'Learning courses (desktop)');

    const operations = await (await browser.newContext({ viewport: { width: 1440, height: 900 } })).newPage();
    await signInAs(operations, 'Supervisor');
    for (const [path, label] of [
        ['/operations/rota', 'Rota'],
        ['/operations/rota/templates', 'Shift templates'],
        ['/operations/rota/open', 'Open shifts'],
        ['/operations/rota/requests', 'Claims and swaps'],
        ['/operations/rota/availability', 'Unavailability'],
        ['/operations/clock/team', 'Team clock'],
        ['/operations/timesheets', 'Timesheets'],
        ['/operations/timesheets/review', 'Timesheets to approve'],
        ['/operations/tasks', 'Tasks'],
        ['/operations/tasks/create', 'Add a task'],
        ['/operations/checklists', 'Checklists'],
        ['/operations/checklists/templates', 'Checklist templates'],
        ['/operations/forms', 'Forms'],
    ]) {
        await visit(operations, path, label);
    }

    const mobile = await (await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true })).newPage();
    await signInAs(mobile, 'Volunteer');
    await visit(mobile, '/change-log', 'Change log (mobile)');
    await visit(mobile, '/dashboard', 'Dashboard (mobile)');
    await visit(mobile, '/operations/clock', 'Clock in and out (mobile)');
    await visit(mobile, '/operations/rota', 'Rota (mobile)');
    await visit(mobile, '/help-desk', 'My tickets (mobile)');
    await visit(mobile, '/help-desk/tickets/create', 'Raise a ticket (mobile)');
    await visitFirstTicket(mobile, '/help-desk', 'Ticket (requester, mobile)');
    await visit(mobile, '/learning', 'My learning (mobile)');
    await mobile.getByRole('button', { name: 'Open menu' }).click();
    await mobile.getByRole('dialog', { name: 'Main menu' }).waitFor();
    await mobile.waitForTimeout(300);
    await audit(mobile, 'Menu drawer (mobile)');

    await keyboardChecks(desktop, mobile);

    const narrow = await (await browser.newContext({ viewport: { width: 320, height: 640 }, isMobile: true, hasTouch: true })).newPage();
    await narrowScreenChecks(narrow);
    await qaSwitcherChecks(desktop, narrow);
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
