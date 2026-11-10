# Operations

The Operations module (`modules/operations`, v0.4.0) runs day-to-day work in
the intranet: rotas, clock in/out, timesheets, tasks, forms and daily
checklists. Chat stays in Teams and team projects stay in Microsoft Planner.

## Who can do what

Access uses the core permission matrix (`app/Core/Access/AccessMatrix.php`)
and the module's own permissions (`modules/operations/src/OperationsPermissions.php`).
"Own team" means the team on the person's profile; management and admin see
every team.

| Area | Members | Supervisors | Management | HR | Admin |
| --- | --- | --- | --- | --- | --- |
| See own published shifts, set unavailability, claim and swap | Yes | Yes | Yes | Yes | Yes |
| Build and publish rotas, shift templates | | Own team | All | | All |
| Approve claims, swaps and timesheets, record missed clock-outs | | Own team | All | | All |
| Clock in and out, build and submit own timesheets | Yes | Yes | Yes | Yes | Yes |
| Volunteer hours totals and CSV | | | Yes | Yes | Yes |
| Assign tasks | | Yes | Yes | | Yes |
| Checklist templates | | Own team | All | | All |
| Sites and the form builder (Admin panel) | | | | | Yes |

A supervisor's team comes from their profile, and a member's line manager
(`manager_id`) also counts as their supervisor for timesheets, tasks and
clock records. Nobody can approve their own claim, swap or timesheet.

## Sites

Admins add sites in **Admin → Operations admin → Sites** and link each one to
a team. Rotas, shift templates and checklists belong to a site. A site can
also have a geofence area (latitude, longitude, radius), used only when the
geofence is approved and switched on.

## Rota

1. Add shift templates (site, name, role, days and times) on **Rota → Shift templates**.
   A shift that ends before it starts runs past midnight.
2. On **Rota**, choose a site and a week and **Create draft week**. Every
   active template becomes an open shift.
3. Assign members. A shift that overlaps the member's unavailability or
   another of their shifts is flagged as a clash. Clashes are warnings, not
   blocks.
4. **Publish and email members.** Each member with shifts gets one email.
   Members only ever see their own published shifts.

Members claim open shifts or ask to swap one of their shifts with a
colleague's at the same site. Nothing changes until a manager approves.
Both members and the managers are emailed, and every request and decision
is in the audit log.

## Clock in and out

Members tap **Clock in** on the intranet or the installed app (the app has a
"Clock in and out" shortcut), then **Start break**, **End break** and
**Clock out**. The page needs a connection: there is no offline mode or
service worker.

`operations:flag-missed-clock-outs` runs every 15 minutes. A record still
open 60 minutes after its shift ended (or 12 hours after clock-in with no
shift) is flagged once and the managers are emailed. They record the
clock-out time on **Team clock**. Both limits can be changed with
`OPERATIONS_MISSED_CLOCK_OUT_GRACE_MINUTES` and
`OPERATIONS_MISSED_CLOCK_OUT_UNSCHEDULED_HOURS`.

### Geofence

The optional clock-in location check is **off**. It needs the DPIA in
[`docs/dpia/clock-in-geofence.md`](../dpia/clock-in-geofence.md) to be
approved first, and then both `OPERATIONS_GEOFENCE_DPIA_APPROVED_AT` (the
approval date) and `OPERATIONS_GEOFENCE_ENABLED=true`. Without the date the
switch does nothing. When on, the browser shares its position once, at
clock-in only, for sites with an area. The server keeps only "pass" or
"fail" and never the coordinates, and clock-in goes ahead either way.

## Timesheets

Members build a weekly timesheet from their finished clock records and
submit it. A manager approves it, which locks it, or returns it with a note
so the member can rebuild and resubmit. Management and HR see approved
volunteer hours per person for a date range, and the CSV export uses the same
totals as the screen.

## Tasks

Tasks are for a person, a team or a shift, with an optional due date and
repeat (daily, weekly or monthly). Completing a repeating task creates the
next one; if it was late, the next due date is the next one still in the
future. Overdue tasks show on the dashboard. Only the people a task is for,
and their managers, can change its status. Comments use the TipTap editor
and are sanitised.

## Forms

Admins build forms in **Admin → Operations admin → Form builder**: short and
long text, number, date, choice, tick box, photo and signature. Each form is
sent to a person, to everyone with a role, or both, and they are emailed on
each submission. Photos and signatures are kept on the private disk
(`storage/app/private/operations/`, persisted and backed up on Cloudron) and
are only shown to the person who sent them, the people it was sent to and
form admins. A typed name is the keyboard-friendly alternative to drawing a
signature. Submissions export to CSV.

Help desk queues arrive in v0.5.0; until then "queue" means a role.

## Checklists

Supervisors and management set up checklists by site and, optionally, by
shift template, with a "complete by" time and one check per line. Publishing
a rota attaches a copy to each matching shift, and a new checklist joins
shifts that are already published. The checklist shows next to the shift on
**My rota**.

The member ticks each check, adds notes and photos, and completes it. Every
check must be ticked or have a note saying why not. A completed checklist is
read-only. `operations:alert-missed-checklists` runs every 5 minutes and
emails the managers once when a checklist is not completed by its time.

## Deploys

`deploy/cloudron/activate-release.sh` runs `OperationsBootstrapSeeder` after
the core seeder on every deploy, so the Operations permissions survive the
core seeder's `syncPermissions` (the lesson from #192). The scheduled
commands run under `schedule:work` on Cloudron (`deploy/cloudron/run.sh`).
