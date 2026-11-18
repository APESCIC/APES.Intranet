# App shell

How people move around the intranet from v0.6.11.

## Layout

| Viewport | What you see |
| --- | --- |
| All widths | Top bar with logo, a Home link, then as many section dropdowns as fit on one row, a **More sections** overflow when needed, notification bell and account menu. Admins switch Staff/Admin from the account menu. |

Site navigation uses a **disclosure** pattern for section groups (buttons that expand compact dropdowns), not an ARIA menubar. Home is a plain link to the dashboard. Keyboard:

- Tab through the top bar in reading order.
- Enter or Space opens a section dropdown, More sections, the account menu or the bell.
- Links inside an open panel are in the tab order.
- Escape closes the open panel and returns focus to the control that opened it.

Interactive shell controls are at least 44 by 44 CSS pixels (`2.75rem`). The shell aims for WCAG 2.2 AA; see [accessibility.md](accessibility.md).

## Top bar pieces

- **Home** — direct link to the dashboard (intranet home); no dropdown.
- **Section dropdowns** — Communications, People, Operations, Support, Apps, More (and Admin groups on admin pages), from `Menu::for`. Compact single-column lists under each label. Shown after Home on one row without a horizontal scrollbar (scroll would clip the panels).
- **More sections** — when labels do not fit, overflowed sections appear under this trailing disclosure as headed link lists.
- **Notification bell** — one wired instance in the top bar. The panel is an HTML popover (~22rem on desktop, near full-width on mobile).
- **Account menu** — avatar disclosure with name and role, Staff/Admin panel links for admins (navigation only; see [admin-menu-mode.md](communications/admin-menu-mode.md)), My profile, Notification settings, Security, local QA pill when enabled, and Sign out. Those account links are not under People.

There is no hamburger or left-rail drawer; the top-level groups are the navigation on every screen size.

The main content column (`.sg-content`) uses **95%** of the visitor’s viewport width on every page that uses the app or error shell. Account and section panels use an opaque surface so text stays readable over the blue shell.

## Related files

- Views: `resources/views/components/shell/`
- Styles: `resources/css/shell.css`
- Scripts: `resources/js/shell/`
- Menu builders: `app/Core/Navigation/`
