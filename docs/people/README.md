# People

The People module (`modules/people`, v0.2.x) holds the directory, the
organisation chart, profiles, the Security page, student placements and the
2FA status report. Cloudron stays the source of truth for accounts: the
intranet reads it and never writes to it. How the nightly sync works is in
[directory-sync.md](directory-sync.md).

## Who can do what

Access uses the core permission matrix (`app/Core/Access/AccessMatrix.php`)
and the module's policies (`modules/people/src/Policies/`).

| Area | Members | Supervisors | Management | HR | Admin |
| --- | --- | --- | --- | --- | --- |
| Directory, organisation chart and profiles of active people | Yes | Yes | Yes | Yes | Yes |
| Edit their own profile | Yes | Yes | Yes | Yes | Yes |
| See HR-only profile fields of other people | | | Yes | Yes | Yes |
| See profiles of people who have left | | | Yes | Yes | Yes |
| Set someone's line manager | | | | Yes | Yes |
| Student placements | Students: their own | Their placements | All | All | All |
| Sign off placement hours | | Their placements | All | All | All |
| 2FA status report and refresh | | | Yes | Yes | Yes |

## Directory and profiles

**People → Directory** lists active people, 24 to a page. It searches name,
email, job title and team, and filters by account type (staff, volunteer or
student), team and skill.

Everyone sees the public fields: name, email, job title, team, phone, mobile,
location, skills, bio, photo and role. The HR-only fields (emergency contact
and employee number) are shown to the person themselves and to HR,
management and admin. Whether someone has 2FA on is shown to HR, management
and admin only. The lists are in `modules/people/src/Support/ProfileFields.php`.

**My profile** lets each person edit their job title, team, phone, mobile,
location, skills, bio, photo and emergency contact. Their name, email and
groups come from Cloudron and change there.

## Organisation chart

**People → Organisation chart** draws a tree from each active person's line
manager. People with no manager, or whose manager has left, start a branch.

HR and admin set a line manager on the person's directory page. Nobody can
manage themselves, and a link that would make a loop is refused. Every change
is audited as `people.manager_changed`. Operations also uses the line manager:
they count as the person's supervisor for timesheets, tasks and clock records
([operations](../operations/README.md)).

## Security

**People → Security** links to the person's Cloudron profile, where they
manage passkeys, authenticator apps (TOTP), backup codes, their password and
sessions, and to the "sign-in methods and 2FA" handbook chapter. The intranet
never stores any of these. 2FA is encouraged, not mandatory. A lost device or 2FA reset goes through the help desk
([help desk](../helpdesk/README.md#lost-device-or-2fa-reset)).

## Student placements

**People → Student placements** shows students their own placements,
supervisors the placements they supervise, and HR and management every
placement.

1. A student opens their placement and logs hours: a date, 15 minutes to 12
   hours, and optional notes. Each entry starts as pending.
2. Their supervisor (or HR or management) approves or returns each entry,
   with optional notes.
3. The placement shows the approved total.

Both steps are audited (`people.placement_hours_logged`,
`people.placement_hours_reviewed`). There is no screen to add a placement
yet; the demo data has examples.

## 2FA status

**Admin → 2FA status** (also open to HR) lists active people and whether they
have an authenticator app on in Cloudron, with when it was last checked.
**Refresh** asks Cloudron again. `people:refresh-totp-status` also runs every
night at 02:30. Only the yes or no is stored.

## Scheduled commands

| Command | When | What |
| --- | --- | --- |
| `people:sync-directory` | Daily at 02:15 | Creates, updates and deactivates members from Cloudron LDAP |
| `people:refresh-totp-status` | Daily at 02:30 | Refreshes the 2FA yes or no flags |

Both are in `routes/console.php` and run under the Cloudron scheduler
(`deploy/cloudron/run.sh`).
