# ADR 0001: Modules and Integrations, and the module registry

- Status: **Accepted** for terminology and disable behaviour (Bambie, 6
  November 2026). The registry design below is **proposed**. It is not
  approved for building.
- Date: 8 November 2026
- Issue: [#156](https://github.com/APESCIC/APES.Intranet/issues/156), part of
  milestone v0.3.5 Platform discovery (Epic
  [#154](https://github.com/APESCIC/APES.Intranet/issues/154))
- Builds on: [architecture.md](../architecture.md) (Core, then Modules, then
  Plugins) and MyAPES Account ADR 0001

This research does **not** authorise registry pilot coding. A pilot needs
Bambie's separate approval of a build milestone.

## Context

The intranet is a modular monolith. Core lives in `app/Core/`. Two modules
exist, `modules/people` and `modules/communications`. Each one registers
itself through a service provider in `bootstrap/providers.php`, a PSR-4 entry
in `composer.json`, and a `MenuContributor` tagged `intranet.menu`. Their
scheduled commands (`people:*`, `comms:*`) are listed centrally in
`routes/console.php`. The `plugins/` folder is empty.

Every module is always on. Admins cannot switch an area off, for example a
Training Module during a pilot or an integration whose supplier is down,
without a code change and a deploy.

## Decision

### Terminology

| Term | Meaning | Can be switched off? |
| --- | --- | --- |
| **Core** | Sign-in, accounts, roles and permissions, audit, settings, health, the app shell and navigation. Lives in `app/Core/`. | Never |
| **Module** | An area of the intranet with its own routes, navigation, data and permissions, for example People, Communications or a future Training Module. Lives in `modules/<slug>/`. | Yes |
| **Integration** | A Module whose main job is to connect to an outside system, for example the LMS links, Microsoft 365 links or DocuSeal. Same layout as a Module (`modules/<slug>/`), marked `kind: integration`. | Yes. Switching it off also stops outbound calls and inbound webhooks. |
| **Plugin** | **Reserved.** Kept for a later, trusted, installable mechanism that needs its own approval. | Not applicable |
| **Cloudron addon** | A platform service that Cloudron provides: MySQL, Redis, mail, OIDC, LDAP, the scheduler and backups. Configured in Cloudron, not in the intranet. | Not from the intranet |

The `plugins/` folder stays empty and reserved. Until a Plugins mechanism is
approved, a feature shared by several modules goes into a Core contract. The
user interface and the docs say "Module" and "Integration", never "Plugin"
or "addon" for intranet code.

### What happens when a Module is switched off

Approved behaviour: the Module becomes **inaccessible**, its background work
is **paused**, and its **data is kept**. Work already in flight is drained or
cancelled safely. Uninstalling or deleting a Module from the admin is out of
scope.

| Surface | When disabled |
| --- | --- |
| Routes and pages | A `module:<slug>` middleware returns 404, so a disabled area does not reveal itself |
| Form actions and APIs | The same middleware. Inbound webhooks for an Integration return 503 with `Retry-After`, so the sender retries later instead of losing the event. |
| Navigation | The Module's `MenuContributor` is not called |
| Dashboard cards and widgets | Not built or shown |
| Search | The Module's search provider is skipped |
| Scheduled commands | Skipped: each schedule entry gets `->when(fn () => $modules->enabled('<slug>'))` |
| Queued jobs | New dispatches are refused. Queued jobs that have not started are released back to the queue with a delay (paused). A job marked cancellable is deleted and audited instead. A job already running finishes. |
| Event listeners | Return straight away without acting |
| Notifications and mail | Not sent. Nothing is queued to send later. |
| Integration dispatch | Outbound clients refuse calls, and the refusal is logged |
| Database tables and data | **Kept as they are.** Migrations still run for disabled Modules, so the schema stays current. |
| Permissions and roles | **Unchanged.** `AccessMatrix` and the Module's seeder still run, so switching it back on restores the same access. |

These are **never** disabled: Core sign-in and roles, audit, settings,
`/healthz` and system checks, the app shell and navigation, and the access
review.

### Registry (proposed design)

- Each Module ships a manifest, `modules/<slug>/module.php`, with its slug,
  name, kind (`module` or `integration`), description, default state, the
  Cloudron addons it needs, and its settings schema.
- The enabled state lives in a Core table, `module_states` (slug, enabled,
  reason, changed by, changed at). With no row, the manifest's default
  applies. The state is cached and read once per request.
- `App\Core\Modules\ModuleRegistry` answers `enabled('<slug>')`. Core never
  imports a Module: it only reads manifests and states.
- Every change is audited as `admin.module.enabled` or
  `admin.module.disabled`, with the reason and no other context.
- Module schedules move from `routes/console.php` into each Module's provider,
  behind the `when` guard above. That move happens in the pilot, not in this
  ADR.

### Settings and secrets

- Each Module declares typed settings: `string`, `int`, `bool`, `enum`, `url`
  or `secret_ref`, each with a default and validation. They are stored in a
  Core `module_settings` table and edited on the Module's admin page.
- One scope only: the whole organisation. APES is a single organisation, so
  nothing here needs tenant or per-team settings.
- A `secret_ref` setting never holds a secret. It holds the environment key
  name (set on Cloudron with the `cloudron-ops` env playbook) and the name of
  the APES Vault item that keeps the value. The admin page shows "set" or
  "not set" only, never the value.

### Dependency rules

The four rules in [architecture.md](../architecture.md) stay. In addition:

1. A Module never requires another Module to be enabled, because a Module
   never imports another Module.
2. A Module that uses an Integration does so through a Core contract. If the
   Integration is disabled, the contract reports "unavailable" and the Module
   hides that feature rather than failing.
3. A Module may list the Cloudron addons it needs. The admin page warns if
   one is missing. It never configures Cloudron.

### Trust boundary

- Modules arrive only as code in this repository, through the normal
  milestone, release and deploy process.
- The admin can switch deployed Modules on or off and change their settings.
  It cannot upload code, install from a marketplace, run Composer or run
  migrations.

### Admin "Modules" screen (notes for later)

Notes only. This ADR does not change `app/Core/Navigation/Menu.php` or any
of issues #148 to #153.

- `/admin/modules`, in the **Admin** menu definition from
  [admin-menu-mode.md](../communications/admin-menu-mode.md). It never goes
  in the Normal or Microsoft 365 groups. Opening it switches an admin into
  Admin mode, as every `/admin/*` URL already does.
- Admin only, through a new `admin.modules.manage` permission, behind
  `fresh-signin`.
- One row per Module: name, kind, state, description and addon warnings.
- Switching off opens a confirmation that lists what stops (from the table
  above) and asks for a reason. Switching on asks the same.
- Each Module's settings page shows its typed settings, and `secret_ref`
  values as "set" or "not set".

## nwidart/laravel-modules: adopt or defer

**Decision: defer.** Keep the existing `modules/<slug>/` layout.

Evidence (Packagist, 8 November 2026):

- `nwidart/laravel-modules` v13.3.0 (28 October 2026), MIT. It needs PHP
  ^8.3 and is tested against `laravel/framework` ^13.0, so it **would work**
  with Laravel 13 on PHP 8.3 (Laragon) and 8.4 (Cloudron). It has no
  Packagist security advisories.
- It needs `wikimedia/composer-merge-plugin`, a Composer plugin that runs
  during `composer install` and merges each module's `composer.json`. That is
  more code running at build time than the repo needs.
- Its enable and disable switch only stops a module's providers from loading,
  using a statuses file by default. On Cloudron each release is a new folder,
  so that file would need to move to `/app/data`. It also does not cover
  paused jobs, schedules, listeners, notifications or webhooks, so a registry
  would still be needed on top.
- Adopting it means moving both modules to its layout and namespaces. That
  is a rewrite with no gain for two modules.
- `internachi/modular` 3.0.2 (MIT, PHP >=8.3, `illuminate/support` ^13) is
  lighter but has the same gap. Also deferred.

Revisit this if the intranet grows past about six modules, or needs module
scaffolding commands.

## Consequences

- The words people see are settled: Module, Integration and Cloudron addon.
  Plugin is held back.
- A future pilot has a checklist: the disable table above.
- `architecture.md` keeps its layer table and points here for terminology.
- Nothing changes in code until a build milestone is approved.

## Checklist against the approved behaviour

- [x] Inaccessible: routes, actions, APIs, navigation, widgets and search
- [x] Paused: schedules, queued jobs, listeners, notifications and
  integration dispatch
- [x] Data retained: tables kept, migrations still run, permissions unchanged
- [x] In flight: running jobs finish; queued jobs are released, or cancelled
  and audited if marked cancellable
- [x] Core security never disabled
- [x] Uninstall and delete out of scope
