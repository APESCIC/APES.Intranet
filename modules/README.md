# Modules

Areas of the intranet. One folder per module, named by its slug: `people`,
`communications`, `microsoft-365` (Integration), `operations`, `helpdesk`,
`learning` and `hr`. See [docs/architecture.md](../docs/architecture.md) for the
layout, the dependency rules and what a new module needs.

## Registry

Core answers â€œis this module on?â€ through
`App\Core\Modules\ModuleRegistry` (ADR
[0001](../docs/adr/0001-modules-and-integrations.md)). Each module ships a
manifest at `modules/<slug>/module.php` with its slug, name, kind
(`module` or `integration`), description, a SemVer **version**, default enabled
state, Cloudron addons, optional `depends_on`, and a typed settings schema.
State and settings live in Core tables (`module_states`, `module_settings`); with
no state row the manifest default applies.

## Layout checklist

Every folder under `modules/<slug>/` that contains a `module.php` manifest must
ship the pieces below. `tests/Feature/Core/ModuleConventionTest.php` discovers
those folders and fails when a required piece is missing.

| Piece | Where it lives | Notes |
| --- | --- | --- |
| Manifest and version | `modules/<slug>/module.php` | Parsed by `ModuleManifest`; SemVer `version` is required |
| Service provider | `modules/<slug>/src/<Studly>ServiceProvider.php` | Registered in `bootstrap/providers.php` and PSR-4 in `composer.json` |
| Routes | `modules/<slug>/routes/web.php` | Loaded with `loadRoutesFrom` in the provider; use `module:<slug>` middleware |
| Menu | `modules/<slug>/src/Navigation/<Studly>MenuContributor.php` | Implements `MenuContributor` and `BelongsToModule`; tagged `intranet.menu` in the provider |
| Permissions | `modules/<slug>/src/<Studly>Permissions.php` and `database/seeders/<Studly>PermissionsSeeder.php` | When the module adds permissions beyond Core; grant admin in the seeder |
| Schedules | Module console commands in `modules/<slug>/src/Console/`, registered in the provider | Each `Schedule::command` uses `ModuleGuards::whenEnabled('<slug>')` |
| Settings | Manifest `settings` schema and optional `modules/<slug>/config/<slug>.php` | Merged in the provider with `mergeConfigFrom` |
| Migrations | `modules/<slug>/database/migrations/` | Loaded in the provider; add the folder to `databaseMigrationsPath` in `phpstan.neon` |
| Dashboard cards | `modules/<slug>/src/Dashboard/` | Optional `DashboardCardContributor` classes tagged `intranet.dashboard` |
| Tests | `tests/Feature/<Area>/` (and `tests/Unit/` where useful) | Not inside the module folder |

Studly names come from the slug (`microsoft-365` â†’ `Microsoft365`).
