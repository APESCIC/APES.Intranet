# Admin menu mode

Admins can switch the sidebar between **Normal** (member) and **Admin**
inventories. The switcher is a navigation preference only:

- It does not change Spatie roles or permissions.
- It is stored in the server session and clears on sign-out.
- Default after sign-in is Normal.
- Opening an `/admin/*` URL auto-switches an admin into Admin mode.
- Non-admins never see the switcher or Admin nav keys.
- HR and legal stay on the Normal menu.
- Microsoft 365 links stay on the Normal menu (#90). Do not put system-admin
  items into the Microsoft 365 group.

This is separate from the Laragon QA “Switch role” control.
