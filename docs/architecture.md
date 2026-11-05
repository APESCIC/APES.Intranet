# Architecture

The intranet follows the same three layers as MyAPES Account (its ADR 0001,
"Core, Modules, and Plugins"): **Core, then Modules, then Plugins**.

| Layer | Folder | Namespace | Holds |
| --- | --- | --- | --- |
| Core | `app/Core/` | `App\Core\` | Sign-in, accounts, roles and permissions, the app shell, settings, audit, health |
| Modules | `modules/<slug>/` | `Modules\<Studly>\` | Areas of the intranet, each with its own navigation and route prefix |
| Plugins | `plugins/<slug>/` | `Plugins\<Studly>\` | Reusable features, enabled per module |

## Dependency rules

1. Core never imports a Module or a Plugin.
2. A module may use Core and the plugins it enables, only through their public
   contracts.
3. A plugin may use Core contracts and the plugins it declares as
   dependencies. It never imports a module.
4. A module never imports another module.

## Package layout

Each module or plugin has one service provider and this layout:

```text
modules/<slug>/src/...
modules/<slug>/{routes,resources/views,lang,config,database/migrations,tests}
plugins/<slug>/src/...
plugins/<slug>/{routes,resources/views,lang,config,database/migrations,database/factories,tests}
```

Add the PSR-4 entry to `composer.json` when the first module or plugin is
created, for example `"Modules\\People\\": "modules/people/src/"`.

## Roles and permissions

Roles come from the 8 approved Cloudron directory groups (see
[cloudron-sign-in.md](cloudron-sign-in.md)) at each sign-in. The
intranet never edits them. `spatie/laravel-permission` stores them.

`App\Core\Access\AccessMatrix` is the single list of Core roles and base
permissions. `RolesAndPermissionsSeeder` writes it to the database and
removes any grant that is not in the list. It holds real roles, not fake data,
so it runs on every deploy:

```bash
php artisan db:seed --class="Database\Seeders\RolesAndPermissionsSeeder" --force
```

| Role | Base permissions |
| --- | --- |
| staff, volunteer, student (account types) | `intranet.access` |
| supervisor | `intranet.access`, `team.supervise` |
| management | `intranet.access`, `team.supervise`, `team.manage`, `hr.access` |
| HR | `intranet.access`, `hr.access` |
| legal | `intranet.access`, `legal.access` |
| admin | Every permission |

Rules for new code:

- Name permissions `{area}.{ability}`, for example `rota.publish`. Core admin
  permissions start with `admin.`.
- Check permissions, not role names: `$user->can('rota.publish')`.
- Put model rules in a policy whose methods call `$user->can(...)`, and
  register it in the layer's service provider. `RolePolicy` is the example.
- A module adds its permissions in its own seeder and grants them to admin
  there.
- `User::isSupervisor()` is `can('team.supervise')`: true for the supervisor,
  management and admin roles. Use it for "my team" features such as rota
  approvals.

## Audit log

Record who did what with `App\Core\Audit\AuditLogger`:

```php
$audit->record('rota.published', $rota, ['week' => '2026-W46']);
```

It stores the time, the signed-in person, the subject, the IP address, the
browser and any context. Entries cannot be edited or deleted one at a time.
`audit:prune` runs daily at 03:15 and deletes entries older than
`AUDIT_LOG_RETENTION_DAYS` (default 730, never less than 30). The Cloudron
cron must run `php artisan schedule:run` every minute for this to happen.

Core already records `auth.signed_in`, `auth.signed_out`,
`auth.sign_in_denied`, `roles.changed`, `admin.roles.viewed` and
`admin.audit.viewed`. Admins read the log at `/admin/audit`.

Do not put special category data (health, DBS results, and so on) in the
context. Record ids, not contents.
