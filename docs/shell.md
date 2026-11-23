# App shell

How people move around the intranet from v0.6.11, with the phone menu panel from v0.6.12 (#422).

## Layout

| Viewport | What you see |
| --- | --- |
| **64rem and wider** | Top bar with logo, a Home link, then as many section mega menus as fit on one row, a **More** overflow when needed, notification bell and account menu. Admins switch Intranet/Admin from the account menu. |
| **Below 64rem** | Top bar with logo, **Menu** (opens the phone menu panel), notification bell and account menu. Section navigation and appearance controls live in the panel. |

Site navigation uses a **disclosure** pattern for section groups (buttons that expand link lists), not an ARIA menubar. On desktop, expanded sections open multi-column mega panels; on phone, the same groups open inside the menu panel. Home is a plain link to the dashboard. Keyboard:

- Tab through the top bar in reading order.
- Enter or Space opens a section menu, More, the phone menu (below 64rem), the account menu or the bell.
- Links inside an open panel are in the tab order.
- Escape closes the open panel or menu and returns focus to the control that opened it.
- On phone, **Open menu** moves focus into the panel (first to **Close menu**). Tab stays inside until the panel closes.

Interactive shell controls are at least 44 by 44 CSS pixels (`2.75rem`). The shell aims for WCAG 2.2 AA; see [accessibility.md](accessibility.md).

## Top bar pieces

- **Home** — direct link to the dashboard (intranet home) on desktop; the same link is the first row in the phone menu panel.
- **Section mega menus** (desktop) — Communications, People (includes Learning), Work, Support (includes soon HR/Agreements and help-desk queues), Apps, and HR when the module adds it (Admin groups on admin pages), from `Menu::for`. Multi-column lists sized so typical groups do not need an inner scroll. Shown after Home on one row without a horizontal scrollbar (scroll would clip the panels). Panels are centred under the top nav so wide menus stay on screen.
- **More** (desktop) — when labels do not fit, overflowed sections appear under this trailing disclosure as headed link lists.
- **Menu** (below 64rem) — opens a full-height **phone menu** panel with Home, expandable section groups (each with a section icon), nested links and the shared appearance controls. There is no left-rail drawer and no duplicated bell in the panel.
- **Brand** — logo and **APES Intranet**. Admins have no Staff/Admin subtitle under the logo; everyone else sees **APES CIC**.
- **Notification bell** — one wired instance in the top bar. The panel is an HTML popover (~22rem on desktop, near full-width on mobile).
- **Account menu** — avatar disclosure with name and role, Intranet/Admin panel links for admins (navigation only; see [admin-menu-mode.md](communications/admin-menu-mode.md)), appearance controls on desktop only, My profile, Notification settings, Security, local QA pill when enabled, and Sign out. Those account links are not under People.

The main content column (`.sg-content`) uses **95%** of the visitor’s viewport width on every page that uses the app or error shell. Account, section and phone menu panels use an opaque surface so text stays readable over the blue shell.

## Related files

- Views: `resources/views/components/shell/`
- Styles: `resources/css/shell.css`
- Scripts: `resources/js/shell/` (`phone-menu.js`, `top-nav.js`, …)
- Menu builders: `app/Core/Navigation/`