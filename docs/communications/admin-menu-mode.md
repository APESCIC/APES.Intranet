# Intranet and Admin panels

People with the Admin role see **Intranet** and **Admin panel** links in the
account (avatar) menu. The page address decides the panel:

- Every `/admin` page (routes named `admin.*`) shows the **Admin** panel. It
  stays selected while an admin moves between admin pages, refreshes, goes
  back, follows a redirect, or opens an admin link directly or in a new tab.
- Every other page shows the **Intranet** (staff) panel, the member menu.
- **Intranet** opens the dashboard. **Admin panel** opens the admin
  overview at `/admin`, which links to the admin sections the person may
  open, including module sections such as Microsoft 365 links.
- Nothing is stored: there is no menu mode in the session, so sign-in always
  lands on the dashboard with the Intranet panel.

The links are navigation only. They are not authorisation:

- They do not change Spatie roles or permissions. Every admin page keeps its
  own permission check, and Access review and System status still ask for a
  fresh sign-in.
- Non-admins never see the panel links or the Admin menu, and get a 403 on
  `/admin`. HR keeps its own access to Access review and 2FA status, shown
  with the Intranet panel.
- Losing the Admin role removes the panel links and the Admin menu on the next
  request.
- Microsoft 365 links stay on the Staff menu (#90). Do not put system-admin
  items into the Microsoft 365 group.

For admins, the brand under the logo shows only **APES Intranet** (no panel
subtitle). Everyone else sees "APES CIC" under the logo.

The old `POST /menu-mode` form address stays only so that pages left open
from earlier releases keep working: it sends admins who chose Admin to the
overview and everyone else to the dashboard, and stores nothing.

This is separate from the local amber QA pill, which switches between fake
accounts and only appears when QA sign-in is on in a local environment.
