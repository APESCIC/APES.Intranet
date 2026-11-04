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
