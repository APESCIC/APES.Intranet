# Staff and Admin panels

People with the Admin role see a **Staff** and **Admin** switch at the top of
the sidebar, in the same place on desktop and in the mobile menu. The page
address decides the panel:

- Every `/admin` page (routes named `admin.*`) shows the **Admin** panel. It
  stays selected while an admin moves between admin pages, refreshes, goes
  back, follows a redirect, or opens an admin link directly or in a new tab.
- Every other page shows the **Staff** panel, the member menu.
- **Staff** in the switch opens the dashboard. **Admin** opens the admin
  overview at `/admin`, which links to the admin sections the person may
  open, including module sections such as Microsoft 365 links.
- Nothing is stored: there is no menu mode in the session, so sign-in always
  lands on the dashboard with the Staff panel.

The switch is navigation only. It is not authorisation:

- It does not change Spatie roles or permissions. Every admin page keeps its
  own permission check, and Access review and System status still ask for a
  fresh sign-in.
- Non-admins never see the switch or the Admin menu, and get a 403 on
  `/admin`. HR keeps its own access to Access review and 2FA status, shown
  with the Staff panel.
- Losing the Admin role removes the switch and the Admin menu on the next
  request.
- Microsoft 365 links stay on the Staff menu (#90). Do not put system-admin
  items into the Microsoft 365 group.

For admins, the brand under the logo reads "Staff panel" or "Admin panel".
Everyone else sees "APES CIC".

The old `POST /menu-mode` form address stays only so that pages left open
from earlier releases keep working: it sends admins who chose Admin to the
overview and everyone else to the dashboard, and stores nothing.

This is separate from the local QA “Switch role” control.
