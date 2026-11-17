# App shell

How people move around the intranet from v0.5.8.

## Layout

| Viewport | What you see |
| --- | --- |
| Desktop (≥64rem) | Top bar with logo, Staff/Admin switch (admins only), grouped disclosure menus, notification bell and account menu. The left rail stays in the page as the source for the mobile drawer clone, but it is not the primary desktop navigation. |
| Tablet and phone | Top bar with hamburger, logo, bell and account avatar. "Open menu" opens a drawer that clones the rail (menu groups, Staff/Admin switch for admins, QA dock when local). The drawer never includes a notification bell. |

Site navigation uses a **disclosure** pattern (buttons that expand panels), not an ARIA menubar. Keyboard:

- Tab through the top bar and drawer in reading order.
- Enter or Space opens a group dropdown, the account menu or the bell.
- Links inside an open panel are in the tab order.
- Escape closes the open panel (or the drawer) and returns focus to the control that opened it.

Interactive shell controls are at least 44 by 44 CSS pixels (`2.75rem`). The shell aims for WCAG 2.2 AA; see [accessibility.md](accessibility.md).

## Top bar pieces

- **Grouped menus** — Home, Communications, People, Operations, Support, Apps, More (and Admin groups on admin pages), from `Menu::for`.
- **Staff / Admin switch** — beside the logo on desktop; inside the drawer on smaller screens. Navigation only; see [admin-menu-mode.md](communications/admin-menu-mode.md).
- **Notification bell** — one wired instance in the top bar. The panel is an HTML popover (~22rem on desktop, near full-width on mobile) so overflow on the rail cannot clip it.
- **Account menu** — avatar disclosure with name and role, My profile, Notification settings, Security, local QA pill when enabled, and Sign out. Those account links are not under People.

## Related files

- Views: `resources/views/components/shell/`
- Styles: `resources/css/shell.css`
- Scripts: `resources/js/shell/`
- Menu builders: `app/Core/Navigation/`
