# Communications

The Communications module (`modules/communications`, v0.3.x) runs the
dashboard, updates, events, the handbook, the policy index and the Microsoft
365 links. SharePoint stays the home for files and Teams for chat; the split
is in [sharepoint-split.md](sharepoint-split.md).

## Who can do what

Access uses the core permission matrix and the module's permissions
(`modules/communications/src/CommunicationsPermissions.php`).

| Area | Members | Management | HR | Admin |
| --- | --- | --- | --- | --- |
| Dashboard, updates, events, handbook and policies | Yes | Yes | Yes | Yes |
| Comment on updates, RSVP to events, acknowledge chapters | Yes | Yes | Yes | Yes |
| Publish updates, events, handbook chapters and policies (`comms.publish`) | | Yes | Yes | Yes |
| Download handbook acknowledgements | | Yes | Yes | Yes |
| Microsoft 365 links, Admin panel (`comms.m365.manage`) | | | | Yes |

## Dashboard

The dashboard shows cards for: unread updates, the next event, student
placements (students only), a nudge to set up a passkey or authenticator
(dismissible for 14 days, `COMMS_2FA_NUDGE_DISMISS_DAYS`), cards from other
modules (such as help desk queues and overdue tasks), the Microsoft 365 apps
and "Chat in Teams".

## Updates

Publishers write an update with the TipTap editor (the HTML is sanitised),
choose its audiences (any roles; none means everyone), and whether it is
pinned and open to comments. Members only see updates for their roles.
Opening an update marks it read, and the page shows how many people have read
it.

The "email alert" tick box is saved with the update, but no email is sent
yet.

## Events

Publishers add an event with a start, optional end, location and optional
reminder time. Members answer going, maybe or declined, and can download it
for their calendar (`.ics`). `comms:send-event-reminders` runs every 5
minutes and emails everyone going once the reminder time has passed, once
per event.

## Handbook

Each chapter has numbered versions. Publishing a new version means members
need to acknowledge it again. Members can search chapters and open older
versions. HR and publishers download who has acknowledged the current
version as CSV.

## Policies

The policy index lists each policy's title, owner, review date and its link
to the SharePoint file (HTTPS only). Members mark a policy as read.
Publishers see the policies due for review in the next 30 days.

## Microsoft 365 links

Admins manage the links in **Admin → Microsoft 365 links**: name, address,
icon, audience (everyone or staff only), order and whether it is in use.
Addresses must be HTTPS on an allowed Microsoft domain (`microsoft.com`,
`cloud.microsoft`, `office.com`, `sharepoint.com`). Changes are audited
(`comms.m365_link_created`, `comms.m365_link_updated`). The links show in the
sidebar and on the dashboard, with the "no licence" help note
([help desk](../helpdesk/README.md#microsoft-365-help-note)). Accessibility
and mobile notes are in [microsoft-links-a11y.md](microsoft-links-a11y.md).

## Staff and Admin panels

The sidebar switch between the Staff and Admin panels is described in
[admin-menu-mode.md](admin-menu-mode.md).

## Scheduled commands

| Command | When | What |
| --- | --- | --- |
| `comms:send-event-reminders` | Every 5 minutes | Emails event reminders that are due |

Settings with defaults are in `modules/communications/config/communications.php`:
`COMMS_2FA_NUDGE_DISMISS_DAYS`, `COMMS_ENGAGE_URL` and
`COMMS_SHAREPOINT_HOST`.
