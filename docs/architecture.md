# Architecture

The intranet follows the same three layers as MyAPES Account (its ADR 0001,
"Core, Modules, and Plugins"): **Core, then Modules, then Plugins**.

| Layer | Folder | Namespace | Holds |
| --- | --- | --- | --- |
| Core | `app/Core/` | `App\Core\` | Sign-in, accounts, roles and permissions, the app shell, settings, audit, health |
| Modules | `modules/<slug>/` | `Modules\<Studly>\` | Areas of the intranet, each with its own navigation and route prefix |
| Plugins | `plugins/<slug>/` | `Plugins\<Studly>\` | Reusable features, enabled per module |

Terminology (Modules, Integrations, reserved Plugins and Cloudron addons) and
the proposed enable and disable registry are in
[ADR 0001](adr/0001-modules-and-integrations.md). Until a Plugins mechanism
is approved, `plugins/` stays empty and shared features go into Core
contracts.

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
| staff, volunteer (account types) | `intranet.access` |
| student (account type) | `intranet.access`, `student.area` |
| supervisor | `intranet.access`, `team.supervise` |
| management | `intranet.access`, `team.supervise`, `team.manage`, `hr.access`, `hr.special_category.view` |
| HR | `intranet.access`, `hr.access`, `hr.special_category.view` |
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

### The permissions matrix

The approved matrix (5 November 2026) has one gate per row, listed in
`AccessMatrix::rows()` and registered by `MatrixPolicies`. Modules call the
gate, never a role name:

| Gate | Who |
| --- | --- |
| `viewTeamRota`, `approveTeamSwap`, `approveTeamTimesheet`, `viewTeamChecklist`, `approveStudentHours` | Supervisors for their own team; management and admin for every team |
| `useStudentArea` | Students and admin |
| `viewHrRecord` | HR, management and admin |
| `viewSpecialCategory` (DBS and health) | HR, management and admin |
| `viewLegalRecord` | Legal and admin |

"Own team" works through `App\Core\Access\Contracts\BelongsToTeam`: a module's
rota, swap, timesheet, checklist or student hours model implements
`isSupervisedBy(User $user)`.

Open HR, DBS, health and legal records only through
`App\Core\Access\SensitiveData`:

```php
$data->openHrRecord($record);
$data->openSpecialCategory($record, SensitiveData::DBS);
$data->openLegalRecord($agreement);
```

It checks the gate and a fresh sign-in for every role, admin included, even
if a route forgot the `fresh-signin` middleware. It also writes the audit
entries. Every view of DBS or health data is audited
(`hr.special_category.viewed`, with the category but never the contents), and
so is every admin or management view of an HR or legal record
(`hr.record.viewed`, `legal.record.viewed`).

## Fresh sign-in

Put the `fresh-signin` middleware on HR, agreements and other sensitive
routes, after `auth`:

```php
Route::middleware(['auth', 'can:hr.access', 'fresh-signin'])->group(...);
```

At sign-in the intranet keeps Cloudron's `auth_time` claim, the time the person
last signed in to Cloudron. If that is older than `FRESH_SIGNIN_MINUTES`
(default 15), the middleware sends them to `/auth/reauth`. That asks Cloudron
for a new sign-in with `prompt=login` and `max_age=0`, then brings them back to
the page they asked for. It applies to every role, admin included. If Cloudron
returns without a new sign-in, the person sees a page that asks them to sign
out of Cloudron and try again, and `auth.fresh_signin_not_honoured` is
audited. Cloudron sends no `amr` or `acr` claim, so the intranet cannot require
MFA by claim.

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
`auth.sign_in_denied`, `auth.fresh_signin_required`,
`auth.fresh_signin_not_honoured`, `roles.changed`, `admin.roles.viewed`,
`admin.audit.viewed`, `admin.system.viewed`, `admin.system.test_email`,
`admin.access_review.viewed`, `hr.record.viewed`,
`hr.special_category.viewed` and `legal.record.viewed`. Admins read the log at
`/admin/audit`.

Admin and HR run the quarterly access review at **Admin > Access review**. It
lists people with more than one senior role, role holders who have not signed
in for 90 days, people in no group, and admin or management views of DBS and
health data. `access-review:remind` emails admin and HR on the first day of
each quarter at 09:00.

Do not put special category data (health, DBS results, and so on) in the
context. Record ids, not contents.
