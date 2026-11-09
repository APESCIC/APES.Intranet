# Demo data

The demo data fills every main page and list of the local intranet with fake
records, so you can click through it, test changes and write repeatable bug
reports. It only exists in your local Laragon database.

```powershell
php artisan demo:seed    # load it, or bring it up to date (composer demo:seed)
php artisan demo:reset   # empty the local database, migrate, load it again (composer demo:reset)
```

`scripts/laragon-setup.ps1` offers to load it, or loads it straight away with
`-SeedDemo`.

## Safety

- Both commands refuse to run unless `APP_ENV=local`, with the message "The
  demo data is fake and only loads when APP_ENV is local." `demo:reset`
  checks before it empties anything. The seeder itself
  (`database/seeders/DemoSeeder.php`) refuses too, so
  `php artisan db:seed --class=...DemoSeeder` cannot load it anywhere else.
- Nothing calls it on Cloudron. Deploys only run the deploy-safe seeders
  (`RolesAndPermissionsSeeder` and `CommunicationsBootstrapSeeder`).
- Everything is made up:
  - names come from Faker's `en_GB` lists;
  - email addresses use `example.test`, and links use `example.org`;
  - phone numbers come from Ofcom's drama ranges (07700 900xxx and
    0113 496 0xxx), so none can belong to anyone;
  - organisations end in "(fake)", and IP addresses use the `192.0.2.x`
    documentation range;
  - there are no photos, so the app shows initials.
- There are no real people, no real APES records, no real animals and no
  copied production data.

## Same data every time

The seeder uses a fixed random seed (`DemoSeeder::SEED`), so every run gives
the same people with the same names, roles, managers and phone numbers. It
updates existing rows instead of adding new ones, so running `demo:seed` again
never duplicates anything. Running `demo:reset` twice in a row gives identical
data. Dates are set relative to today, so "upcoming" events stay upcoming.

## What it contains

| Area | Records |
| --- | --- |
| People | 50 people, `person01@example.test` to `person50@example.test`: 17 staff, 17 volunteers and 16 students, with job titles, teams, skills, phone numbers and emergency contacts |
| Permission roles | `person01` admin, `person04` management, `person07` HR, `person10` legal, and `person13`, `person16` and `person19` supervisors (all staff) |
| Organisation chart | `person01` at the top, staff managing staff in fours, and every volunteer and student reporting to a member of staff |
| QA accounts | 12 accounts for the QA role switcher (see below) |
| Placements | One placement for each of the 16 students, at made-up providers, each with a supervisor |
| Placement hours | 6 entries per placement (96 in all): 3 approved, 1 returned with a note and 2 pending |
| Updates | 30 updates (2 pinned), some for staff, volunteers or students only, with about 50 comments and 500 reads |
| Events | 12 events, 4 past and 8 upcoming, with 150 RSVPs (going, maybe, declined) |
| Handbook | The 2 deploy-safe chapters plus 4 demo chapters |
| Policies | 8 policy documents (one overdue for review) with 100 reads |
| Microsoft 365 links | The 8 deploy-safe links |
| Audit log | 40 entries over the last 8 days, for the admin audit page |

## Test logins

The intranet signs in through Cloudron only, so there are no passwords. In
the local environment, the QA role switcher signs you in as a fake account for
any role, with one click:

1. Open <http://apes-intranet.test/qa>.
2. Choose the account.

| Account | Roles |
| --- | --- |
| `qa-staff@example.test` | Staff |
| `qa-volunteer@example.test` | Volunteer |
| `qa-student@example.test` | Student |
| `qa-supervisor@example.test` | Supervisor |
| `qa-management@example.test` | Management |
| `qa-hr@example.test` | HR |
| `qa-legal@example.test` | Legal |
| `qa-admin@example.test` | Admin |
| `qa-staff-hr@example.test` | Staff and HR |
| `qa-staff-management@example.test` | Staff and Management |
| `qa-volunteer-supervisor@example.test` | Volunteer and Supervisor |
| `qa-staff-admin@example.test` | Staff and Admin |

The switcher only works when `QA_LOGIN_ENABLED=true` and `APP_ENV=local`, as
in `.env.laragon.example`. Anywhere else, every `/qa` route returns 404. It is
off on Cloudron.

The 50 demo people appear in the directory, the organisation chart, comments,
RSVPs and placements, but you cannot sign in as them.
