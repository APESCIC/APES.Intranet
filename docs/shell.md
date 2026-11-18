# App shell

How people move around the intranet from v0.6.11.

## Layout

| Viewport | What you see |
| --- | --- |
| All widths | Top bar with logo, a Home link, then as many section mega menus as fit on one row, a **More** overflow when needed, notification bell and account menu. Admins switch Intranet/Admin from the account menu. |

Site navigation uses a **disclosure** pattern for section groups (buttons that expand multi-column mega panels), not an ARIA menubar. Home is a plain link to the dashboard. Keyboard:

- Tab through the top bar in reading order.
- Enter or Space opens a section mega menu, More, the account menu or the bell.
- Links inside an open panel are in the tab order.
- Escape closes the open panel and returns focus to the control that opened it.

Interactive shell controls are at least 44 by 44 CSS pixels (`2.75rem`). The shell aims for WCAG 2.2 AA; see [accessibility.md](accessibility.md).

## Top bar pieces

- **Home** — direct link to the dashboard (intranet home); no dropdown.
- **Section mega menus** — Communications, People (includes Learning), Work, Support (includes soon HR/Agreements and help-desk queues), Apps, and HR when the module adds it (Admin groups on admin pages), from `Menu::for`. Multi-column lists sized so typical groups do not need an inner scroll. Shown after Home on one row without a horizontal scrollbar (scroll would clip the panels).
- **More** — when labels do not fit, overflowed sections appear under this trailing disclosure as headed link lists.
- **Brand** — logo and **APES Intranet**. Admins have no Staff/Admin subtitle under the logo; everyone else sees **APES CIC**.
- **Notification bell** — one wired instance in the top bar. The panel is an HTML popover (~22rem on desktop, near full-width on mobile).
- **Account menu** — avatar disclosure with name and role, Intranet/Admin panel links for admins (navigation only; see [admin-menu-mode.md](communications/admin-menu-mode.md)), My profile, Notification settings, Security, local QA pill when enabled, and Sign out. Those account links are not under People.

There is no hamburger or left-rail drawer; the top-level groups are the navigation on every screen size.

The main content column (`.sg-content`) uses **95%** of the visitor’s viewport width on every page that uses the app or error shell. Account and section panels use an opaque surface so text stays readable over the blue shell.

## Related files

- Views: `resources/views/components/shell/`
- Styles: `resources/css/shell.css`
- Scripts: `resources/js/shell/`
- Menu builders: `app/Core/Navigation/`
