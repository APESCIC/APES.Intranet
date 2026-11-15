# Modules

Areas of the intranet. One folder per module, named by its slug: `people`,
`communications`, `operations`, `helpdesk` and `hr`. See
[docs/architecture.md](../docs/architecture.md) for the layout, the dependency
rules and what a new module needs.

## Registry

Core answers “is this module on?” through
`App\Core\Modules\ModuleRegistry` (ADR
[0001](../docs/adr/0001-modules-and-integrations.md)). Each module ships a
manifest at `modules/<slug>/module.php` with its slug, name, kind
(`module` or `integration`), description, default enabled state, Cloudron
addons, optional `depends_on`, and a typed settings schema. State and
settings live in Core tables (`module_states`, `module_settings`); with no
state row the manifest default applies.
