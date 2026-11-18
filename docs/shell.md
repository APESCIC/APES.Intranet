# App shell

How people move around the intranet from v0.6.11.

## Layout

| Viewport | What you see |
| --- | --- |
| All widths | Top bar with logo, top-level group buttons after the logo (wrap on narrow screens), notification bell and account menu. Each group opens a wider mega panel with a section heading and that group’s links. Admins switch Staff/Admin from the account menu. |

Site navigation uses a **disclosure** pattern (buttons that expand mega panels), not an ARIA menubar. Keyboard:

- Tab through the top bar in reading order.
- Enter or Space opens a group mega panel, the account menu or the bell.
- Links inside an open panel are in the tab order.
- Escape closes the open panel and returns focus to the control that opened it.

Interactive shell controls are at least 44 by 44 CSS pixels (`2.75rem`). The shell aims for WCAG 2.2 AA; see [accessibility.md](accessibility.md).

## Top bar pieces

- **Grouped mega menus** — Home, Communications, People, Operations, Support, Apps, More (and Admin groups on admin pages), from `Menu::for`. Always shown after the logo; panels use a multi-column layout when a group has enough links.
- **Notification bell** — one wired instance in the top bar. The panel is an HTML popover (~22rem on desktop, near full-width on mobile).
- **Account menu** — avatar disclosure with name and role, Staff/Admin panel links for admins (navigation only; see [admin-menu-mode.md](communications/admin-menu-mode.md)), My profile, Notification settings, Security, local QA pill when enabled, and Sign out. Those account links are not under People.

There is no hamburger or left-rail drawer; the top-level groups are the navigation on every screen size.

The main content column (`.sg-content`) uses **95%** of the visitor’s viewport width on every page that uses the app or error shell. Account and mega menu panels use an opaque surface so text stays readable over the blue shell.

## Related files

- Views: `resources/views/components/shell/`
- Styles: `resources/css/shell.css`
- Scripts: `resources/js/shell/`
- Menu builders: `app/Core/Navigation/`
