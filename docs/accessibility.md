# Accessibility

The intranet aims for WCAG 2.2 AA from the first screen. This page records the
baseline audit for v0.1.x Foundations and the rules for new pages.

## Automated check

`npm run test:a11y` (`scripts/a11y/check.mjs`) runs in the `ci` check on every
pull request. It:

- runs axe-core with the WCAG 2.0, 2.1 and 2.2 A and AA rules on the sign-in
  page, privacy notice, 404 and 403 error pages, dashboard (desktop and
  mobile), roles screen, audit log and the open mobile menu
- signs in through the QA role switcher as Staff, Admin and Volunteer
- presses real keys to check the skip link, focus order, focus rings and the
  mobile menu's focus trap
- fails on any serious or critical axe issue, or any keyboard failure

Run it locally against Laragon with the Edge browser that comes with Windows:

```powershell
$env:BASE_URL = 'http://apes-intranet.test'
$env:A11Y_BROWSER_CHANNEL = 'msedge'
npm run test:a11y
```

## Baseline audit, 4 November 2026

Laragon preview, Edge (Chromium), axe-core 4.14 via `@axe-core/playwright`.

| Page | axe result |
| --- | --- |
| Sign in | No issues. Contrast confirmed |
| Privacy notice | No issues. Contrast confirmed |
| Error 404 | No issues. Contrast confirmed |
| Error 403 | No issues. Contrast confirmed |
| Dashboard, desktop | No issues. Contrast confirmed |
| Roles | No issues. Contrast confirmed |
| Audit log | No issues. Contrast confirmed |
| Dashboard, mobile | No issues. Contrast confirmed |
| Mobile menu open | No issues. Contrast confirmed for the menu; the page behind the blurred overlay could not be measured (expected) |

### Colour contrast

Measured from the theme colours (WCAG relative luminance). Body text needs
4.5:1.

| Text | Background | Ratio |
| --- | --- | --- |
| Ink `#12262c` | Base `#d2f4fa` | 13.5:1 |
| Muted `#40676a` | Base `#d2f4fa` | 5.4:1 |
| Muted `#40676a` | White | 6.2:1 |
| Teal links `#107481` | Base `#d2f4fa` | 4.7:1 |
| Teal links `#107481` | White | 5.5:1 |
| White on teal buttons | Teal `#107481` | 5.5:1 |
| Warning `#8a4b0b` | Warning mist `#fdefd3` | 6.0:1 |

`--color-sg-accent` (`#67c3ce`) fails contrast as text. Use it for decoration
only.

### Keyboard

Checked with real key presses in the automated check, and by hand in the
Laragon preview:

- The first Tab shows "Skip to main content". Enter moves focus to the main
  content.
- Focus moves through the brand link, then the menu in reading order, then the
  user dock, the page content and the footer.
- Every focusable element shows a 2 to 3px blue (`#2563eb`) outline.
- On tablet and mobile, "Open menu" opens the menu as a dialog and moves focus
  to "Close menu". Tab stays inside the menu. Escape or the backdrop closes it
  and returns focus to "Open menu".
- Planned features ("Soon") are not focusable and are announced as "not
  available yet".
- "Chat in Teams" says that it opens in a new tab.
- `prefers-reduced-motion` turns off the menu slide and other transitions.

### Screen reader

The accessibility tree was reviewed in Edge. Every page has one `main`
landmark, a labelled "Main menu" navigation, a "Legal" footer navigation and
one `h1`. The roles and audit tables have captions, the current menu item has
`aria-current="page"`, and the draft privacy notice has `role="note"`.

A full pass with a real screen reader has **not been done yet**. Before
launch (issue #74), someone should go through the sign-in page, dashboard,
menu and audit log with NVDA on Windows and VoiceOver on iPhone, and record
the result here:

| Screen reader | Browser | Date | Tester | Result |
| --- | --- | --- | --- | --- |
| NVDA | Edge or Firefox | | | |
| VoiceOver | Safari on iOS | | | |

## Rules for new pages

- Use the `x-layouts.app` layout. It provides the skip link, landmarks and
  menu. Give every page a `title`; it becomes the only `h1`.
- Headings go in order (`h2` inside the page, then `h3`). Do not skip levels.
- Every form control needs a visible `<label>`. Put errors next to the field
  and link them with `aria-describedby`.
- Tables need a `<caption>` (it can be `sr-only`) and `scope` on header cells.
- Icons are decoration (`aria-hidden`). Icon-only buttons need an
  `aria-label`.
- Links that open a new tab say so, for example with
  `<span class="sr-only">(opens in a new tab)</span>`.
- Use the theme colours above. Never use the accent colour for text.
- Add new pages to `scripts/a11y/check.mjs` so CI checks them.
