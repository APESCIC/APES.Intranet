# Help desk

The help desk module (`modules/helpdesk`, v0.5.0) lets members raise tickets
to IT or Management and follow them in the intranet. Chat stays in Teams.

## Who can do what

Access uses the module's permissions (`modules/helpdesk/src/HelpdeskPermissions.php`).

| Area | Members | Management | Admin |
| --- | --- | --- | --- |
| Raise tickets, see and reply to their own | Yes | Yes | Yes |
| Work the Management queue | | Yes | Yes |
| Work the IT queue | | | Yes |
| Help desk reports and CSV, for the queues they work | | Yes | Yes |
| Topics and routing (Admin panel) | | | Yes |

"Working a queue" means seeing its tickets, replying, adding private notes,
assigning, changing status, topic and priority, and merging duplicates.
Members only ever see their own tickets and the public replies on them.

## Raising a ticket

**Help desk → Raise a ticket.** The member chooses a topic, and the topic
decides the queue. They can choose how urgent it is, or keep the topic's
usual priority. They are told not to include passwords, codes or other
people's personal details.

A link can preselect a topic: `/help-desk/tickets/create?category=<slug>`,
for example the Microsoft 365 help note uses `microsoft-365`.

## Working a ticket

Queue members open **Help desk queues → IT queue** or **Management queue**.
The filters are: open and waiting, assigned to me, not assigned, past SLA,
resolved and closed, and everything.

On a ticket, queue members can:

- reply to the requester (the requester sees it and is emailed);
- add a **private note**, which only the people who work that queue see.
  The requester never sees it, and it is never put in an email;
- assign it to someone who works the queue. Every change is written to the
  audit log (`helpdesk.ticket_assigned`) and shows as a private note;
- change the status: open, waiting for the requester, resolved, closed. A
  reply from the requester reopens a waiting or resolved ticket. Closed
  tickets take no more replies;
- change the topic or priority. A topic in another queue moves the ticket
  there and drops the assignee;
- merge a duplicate into another open ticket. The duplicate closes and its
  requester is told where the request carries on.

## Topics and SLA

Admins set topics in **Admin → Help desk admin → Topics and routing**: name,
queue, usual priority, order, whether it is in use, and whether it needs an
identity check before it can close. Retire a topic rather than deleting it.
The deploy seeds the default topics once (`HelpdeskBootstrapSeeder`) and
leaves admins' changes alone.

SLA targets are in wall-clock hours from when the ticket was raised, set in
`modules/helpdesk/config/helpdesk.php`:

| Priority | First reply within | Resolved within |
| --- | --- | --- |
| Urgent | 4 hours | 24 hours |
| High | 24 hours | 72 hours |
| Normal | 48 hours | 120 hours |
| Low | 120 hours | 240 hours |

The reply clock stops at the first public reply from someone who works the
queue, or when the ticket is resolved. Changing the priority resets both
targets from when the ticket was raised. A ticket past either target shows
**Past SLA** in the queue, and queue members see the count on their
dashboard.

## Lost device or 2FA reset

The **Lost device or 2FA reset** topic goes to the IT queue and needs an
identity check:

1. Check who the member is: call them back on a phone number we already
   hold, or ask their manager to check in person or on a video call.
2. Record the check on the ticket. Do not write phone numbers, codes or
   recovery keys in the note.
3. Reset 2FA on the member's Cloudron user page, then press **Record 2FA
   reset**.

The ticket cannot be resolved or closed until the check is recorded. Only
the IT queue can record checks and resets, and both are in the audit log
(`helpdesk.identity_checked`, `helpdesk.two_factor_reset`). Any change to
Cloudron's 2FA settings is made by the APES IT Administrator with Bambie's
approval; 2FA is encouraged, not mandatory, so no Cloudron change is needed.

## Email

Emails go through Cloudron sendmail (locally to `storage/logs/laravel.log`):

- when a ticket is raised: the requester, and everyone who works the queue;
- on each public reply from the queue: the requester;
- on a reply from the requester: the assignee, or the queue if nobody is
  assigned;
- when it is assigned: the new assignee;
- when it is resolved or closed: the requester, once.

Emails are plain text, never contain private notes, and link to the ticket.

**Reply-by-email is not available.** The Cloudron app has no `recvmail`
addon, so it cannot receive mail (decided on #45, 11 November 2026). Emails
ask people to reply on the intranet.

## Reports

**Help desk queues → Help desk reports** shows, for tickets raised in a
period (the last 30 days by default) and for each queue the person works:
raised, still open, resolved or closed, missed an SLA target, and the
average time to the first reply. Each number opens the matching tickets in
the queue, filtered to the same period, and **Download CSV** exports the
same figures.

## Microsoft 365 help note

The Microsoft 365 menu section and dashboard tile show a short note for
members who reach a Microsoft sign-in or "no licence" page, with links to
the handbook chapter "Microsoft 365 accounts and licences" and to a new IT
ticket. No app is hidden by licence (decided 4 November 2026). The wording
is in `modules/communications/src/Support/MicrosoftHelp.php`.
