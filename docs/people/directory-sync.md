# Directory sync (Cloudron API)

Read-only sync keeps intranet members in step with Cloudron. OIDC remains the
only sign-in path; groups at sign-in come from the OIDC `groups` claim.

## Commands

- `php artisan people:sync-directory` — create, update or deactivate members
  from the Cloudron read-only API
- Scheduled nightly at 02:15 (also ends sessions for deactivated members)
- `php artisan people:refresh-totp-status` — optional yes/no TOTP flags from the
  Cloudron users API (`totpEnabled`)

## How it works

- The sync calls `GET /api/v1/users` and related Cloudron API endpoints with
  `CLOUDRON_API_TOKEN`. It never writes to Cloudron.
- Users without an email are skipped.
- Intranet access still depends on membership of the approved `intranet.*`
  groups. Someone with none of those groups is deactivated on the intranet
  (roles cleared, `is_active` false).
- Disabled Cloudron accounts: treat missing intranet groups (or removal from
  the users list) as the deprovision signal.
- **Safety stop:** if a run would deactivate more than 20% of active directory
  members, it stops and changes nothing (see `MemberDirectorySync`).

## 2FA status (#85)

Cloudron’s `GET /api/v1/users` includes a boolean `totpEnabled` per user. The
intranet stores only that yes/no flag. The API token lives in
`CLOUDRON_API_TOKEN` on the server and is never committed.
