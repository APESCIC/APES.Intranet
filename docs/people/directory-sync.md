# Directory sync (Cloudron LDAP)

Read-only sync keeps intranet members in step with Cloudron. OIDC remains the
only sign-in path.

## Commands

- `php artisan people:sync-directory` — create, update or deactivate members
- Scheduled nightly at 02:15 (also ends sessions for deactivated members)
- `php artisan people:refresh-totp-status` — optional yes/no TOTP flags from the
  Cloudron users API (`totpEnabled`)

## Cloudron 10.1.6 notes (recorded for #130)

- LDAP is searched read-only with the addon bind DN. The sync never writes to
  LDAP.
- Users without a `mail` attribute are skipped.
- Intranet access still depends on membership of the approved `intranet.*`
  groups. Someone in LDAP with none of those groups is deactivated on the
  intranet (roles cleared, `is_active` false).
- Disabled Cloudron accounts: treat missing intranet groups (or removal from
  the users tree) as the deprovision signal. Do not rely on a separate
  “disabled” LDAP flag in this release.
- OIDC `groups` claims are not required for sync. Group membership is read
  from LDAP `memberOf` (or the configured group attribute) at sync and at
  sign-in via `DirectoryGroups`.

## 2FA status (#85)

Cloudron’s `GET /api/v1/users` includes a boolean `totpEnabled` per user. The
intranet stores only that yes/no flag. The API token lives in
`CLOUDRON_API_TOKEN` on the server and is never committed.
