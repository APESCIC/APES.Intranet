# Microsoft 365 Integration — rollback note (v0.6.5)

The Microsoft 365 app links moved out of Communications into
`modules/microsoft-365` (`kind: integration`) in v0.6.5. The
`microsoft_links` table and `comms.m365.manage` permission name stay the
same so role grants and stored links keep working.

## Re-enable if disabled

1. Sign in as an Admin and open **Admin → Modules**.
2. Find **Microsoft 365** and enable it (give a short reason).
3. Or from the app shell (Herd / Cloudron artisan):

```bash
php artisan tinker --execute="app(\\App\\Core\\Modules\\ModuleRegistry::class)->enable('microsoft-365', 'Rollback re-enable', \\App\\Models\\User::query()->role('admin')->first());"
```

Nav, the dashboard tile and `/admin/m365-links` come back. Link rows are
not deleted when the module is disabled.

## What moved

| Before (Communications) | After (Microsoft 365) |
| --- | --- |
| `Modules\Communications\Models\MicrosoftLink` | `Modules\Microsoft365\Models\MicrosoftLink` |
| Admin CRUD controller / policy / allowlist / help note | Same classes under `modules/microsoft-365/src` |
| Staff nav group + dashboard tile | `Microsoft365MenuContributor` + `Microsoft365DashboardCards` |
| Config `communications.m365_url_allowlist` | `microsoft-365.m365_url_allowlist` |
| Seeded by `CommunicationsBootstrapSeeder` | `Microsoft365BootstrapSeeder` (also on Cloudron activate) |

Communications still owns the handbook chapter *Microsoft 365 accounts and
licences* and the dashboard shell. SharePoint and Teams URLs elsewhere stay
where they were.

## Local / Cloudron checks

- Local: disable and re-enable on https://apes-intranet.test under Admin →
  Modules; confirm 404 on `/admin/m365-links` while disabled and that links
  reappear.
- Cloudron: same Admin path after deploy; `activate-release.sh` re-runs
  `Microsoft365BootstrapSeeder` after the core and Communications seeders.
