# Agent instructions for APES Intranet

Cursor's user rules and skills set how work is done here (branches, issues, pull requests, merge, release, deploy, ship gate, decisions and safety). This file describes this repository and fills the named slots below. If anything here disagrees with them, Cursor's rules win and this file is corrected in the same milestone.

Cursor user rules and personal skills (Agent Store) own process; this file fills named slots only.

The owner is Bambie Murphy, Director. Write UK English (organisation, behaviour, colour, licence) in the product, docs, issues and pull requests. Words in *italics* are in the [glossary](docs/glossary.md). Never print, paste or commit secrets. Secrets live only in APES Vault (Bitwarden).

## About the app

The private intranet for APES CIC staff, volunteers and students: updates, the directory, rotas, clock in and out, tasks, forms and the help desk, with HR, training, agreements and hiring to come. Laravel app, live at <https://intranet.apes.org.uk> on *Cloudron*. Owner decisions: [docs/decisions.md](docs/decisions.md).

- Repository: `APESCIC/APES.Intranet`. Work only in this repository (plus `docs/apes-intranet/` in private `APESCIC/.github-private` for internal-only docs).

## Stack and architecture

- Laravel on PHP 8.4, MySQL, Vite on port 5174, Redis and mail via Cloudron addons in production.
- Local stack: Laravel Herd Pro only, with fake demo data. Never real HR, student, volunteer or agreement data.
- Key paths: `app/`, `resources/`, `routes/`, `database/`, `docs/`, `.cursor/skills/`, `deploy/cloudron/`.
- App shell (v0.6.11+, phone menu #422): top bar with logo (no Staff/Admin subtitle for admins); **64rem+** Home link and section mega menus (More overflow when needed); **below 64rem** Menu button opening a full-height phone menu panel (section icons, appearance controls); notification bell and account menu (Intranet/Admin panel links for admins); content column at 95% viewport width; opaque panels. No left-rail drawer. Layout and keyboard notes: [docs/shell.md](docs/shell.md).
- Site footer (v0.6.18+): grouped links (Legal, Trust and help, About) from `footer_links`, plus the existing version pill; Privacy and Cookies stay locked for Everyone. Legal pages how-to: [docs/legal-pages.md](docs/legal-pages.md).

## Local development and preview

| Item | Value |
| --- | --- |
| Clone | `C:\Users\BambieMurphy\Documents\GitHub\APES.Intranet` |
| Herd site | `apes-intranet` (linked; PHP 8.4 isolated) |
| Preview URL | `https://apes-intranet.test` (Herd secure; `http://` redirects) |
| Preview command | `scripts\preview.ps1` (live preview gate: syncs code, runs pending migrations locally, idempotent `LegalPagesSeeder` only, `/healthz`, Vite), then `npm run dev` / Vite |
| HMR port | 5174 (Vite) |
| Env file | `.env.example` (key names only, never values) |
| Database | MySQL `apes_intranet_local` (Herd MySQL 8.4 on 127.0.0.1:3306, `root`, empty password) |
| Mail | Herd mail catcher (`MAIL_HOST=127.0.0.1`, `MAIL_PORT=2525`) |
| Setup | `scripts\herd-setup.ps1` (or `composer setup`); demo: `php artisan demo:seed` |

Laravel Herd is the only local stack. Use fake demo data only. Values: [docs/herd/profile.json](docs/herd/profile.json); guide: [docs/local-development.md](docs/local-development.md). Rule: [`.cursor/rules/herd.mdc`](.cursor/rules/herd.mdc). Gate skill: [`.cursor/skills/live-preview-gate/SKILL.md`](.cursor/skills/live-preview-gate/SKILL.md).

### Local development with Laravel Herd

This project runs on Laravel Herd. Run `herd site:info` for the URL, PHP version and connected services.

- Dot-source `scripts\herd-env.ps1` (or use `herd php` / `herd composer`) so PHP 8.4, Composer and Node 22 win over Herd's newer global PHP.
- Do not use Herd's badge or app "dev" stack (`php artisan dev`): it clashes with Vite on 5174. Use `scripts\preview.ps1` and `npm run dev`.
- Sign in locally with the amber **Developer sign-in** panel on `/login`; the amber **QA** pill switches role (`/qa` fallback).
- Herd MCP (`user-herd`) shows the site's last error, logs and caught mail.

## Tests and checks

| Check | Command |
| --- | --- |
| Tests | `composer test` (or `php artisan test`; avoid `--parallel` for footer/changelog/`/healthz` flakes) |
| Format | `php vendor/bin/pint` |
| Static analysis | `php vendor/bin/phpstan analyse --memory-limit=1G` |
| Accessibility | Manual on Herd (`https://apes-intranet.test`) in Cursor's browser; `npm run test:a11y` only prints guidance |
| CI workflows | `.github/workflows/` (see [docs/workflows.md](docs/workflows.md)) |

## This repo's shipping flow

Each *milestone* is one version (`vMAJOR.MINOR.PATCH` in the title) and ships once. **This repo's flow wins** over personal Cursor skills that use one PR per milestone, `Closes #n`, `cursor/feature/...` branches, or `Close-Milestone.ps1`.

1. **Work.** Each issue gets an issue branch from the version branch, commits, and a PR into the version branch. After merge and green checks: "Work complete" comment, then close the issue. Then the next issue.
2. **Merge.** When every issue is closed, merge `main` into the version branch and open one PR into `main`. After the ship-gate answer, merge once with a merge commit (never squash or rebase).
3. **Release.** One tag `vX.Y.Z` and one GitHub release.
4. **Deploy.** One Cloudron deploy of that tag; `/healthz` is evidence. Then close the Epic and the milestone.

"Complete the next version" means the open milestone with the lowest version above the latest tag, never GitHub's milestone number. Hotfix need written approval on the issue. Full flow: [CONTRIBUTING.md](CONTRIBUTING.md). Skills: `.cursor/skills/`. Rule: [`.cursor/rules/shipping.mdc`](.cursor/rules/shipping.mdc).

Development is tracked only in GitHub issues, sub-issues and `vMAJOR.MINOR.PATCH` milestones — never Linear.

## Everyday commands

| Command | What it does |
| --- | --- |
| `/next-version` | Start or resume the next version |
| `/work-on-issue` | Work one issue from start to close |
| `/plan` | Write a plan and wait for approval |
| `/status` | Version, open issues, PRs (read-only) |
| `/finish-version` | Release note, version PR, ship gate, merge |
| `/release` | Tag and GitHub release |
| `/deploy` | Cloudron deploy, evidence, close-out |
| `. .\scripts\herd-env.ps1` | Herd PHP 8.4, Composer, Node 22 on `PATH` |
| `scripts\preview.ps1` | Live preview gate (migrate pending + idempotent LegalPagesSeeder) |
| `scripts\herd-setup.ps1` | Set up or repair the Herd site |
| `npm run dev` | Vite on 5174 |
| `php artisan demo:seed` / `demo:reset` | Load / wipe+reload fake demo data (`reset` asks first) |
| `composer milestone:next` / `milestone:status -- vX.Y.Z` | Next version names / open issues |

## Workflow slots

| Slot | Value |
| --- | --- |
| Milestone / version branch | `milestone/vX-Y-Z-<slug>` (`composer milestone:branch`) |
| Issue branch | `<type>/<issue>-<slug>` from the version branch |
| Issue PR title | `[vX.Y.Z] <summary> (#<issue>)` into the version branch |
| Version PR into `main` | `vX.Y.Z: <milestone name>`, body `Refs #<epic>` (not `Closes`) |
| Version files / release note | `composer milestone:bump -- vX.Y.Z` → `changelog/releases/vX.Y.Z.json` |
| Release tooling | Annotated tag `vX.Y.Z`; title `vX.Y.Z <milestone name>` |
| Area labels | As on sibling issues (for example `area:local-dev`, `area:cloudron`) |
| Issue title prefix | `[Type] Short title` |
| Issue comments | Templates in `.cursor/skills/work-on-issue/templates/` (not `Add-IssueUpdate.ps1`) |
| Milestone close | `gh api` step in `deploy-version` skill (not `Close-Milestone.ps1`) |

## Deploy and health

| Item | Value |
| --- | --- |
| Target | Cloudron LAMP app |
| Cloudron app ID | `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264` |
| Domain | <https://intranet.apes.org.uk> |
| Deploy command | `gh workflow run deploy-cloudron.yml -f tag=vX.Y.Z` then `gh run watch` |
| Deploys | The published release tag only, via GitHub Actions (never a branch, never local `deploy.ps1`). Merge and release do not auto-deploy |
| Auto-deploy couplings | None |
| Health URL | <https://intranet.apes.org.uk/healthz> |
| Version field | JSON `version` (tag) and `revision` (first 12 chars of the tag commit) |
| Secrets live in | APES Vault; Cloudron `shared/.env` (never commit values) |

## Rules you must follow

- Never commit, print or paste passwords, tokens, keys or `.env` values.
- Fake data only.
- Never commit to `main`, force-push, or rebase a shared branch.
- No merge into `main`, release or deploy without the ship-gate answer in the current chat.
- The Project coordinator chat title stays `Next version plan – APES.Intranet` unless Bambie asks to change it.
- Ask before `demo:reset`, installing any system tool, or ad-hoc migrations outside `scripts\preview.ps1` / `scripts\herd-setup.ps1` (those already run pending migrations locally).
- Do not rename or delete milestones or labels; do not change DNS or Cloudflare.
- Docs live in this repo; anything that must not be public goes in `APESCIC/.github-private` `docs/apes-intranet/`. Never hand a document off to SharePoint.
- Free GitHub features only. Security reviews use tests and the `review-security` skill, never Aikido.

## Decisions and gotchas

- 2026-11-22: Admin dashboard shared default is gated by `INTRANET_DIRECTOR_EMAIL` → `config('intranet.director_email')` (email match, not a Spatie role). Name only in `.env.example`; value in APES Vault / Cloudron env.
- 2026-11-14: Laravel Herd Pro is the only local set-up (Bambie, #282), replacing Laragon (#213, #344).
- Herd's global `php` may be newer than 8.4: always use `herd-env.ps1` / `herd php`.
- After Herd restarts, `herd services:list` can show MySQL stopped while port 3306 still answers; start by service id. Trust `scripts\preview.ps1`.
- Stale folders on `PATH` (including leftover Laragon `bin` paths) must not win over Herd; after `herd-env.ps1`, `(Get-Command php).Source` must show `.config\herd\bin\php84`.
- Herd injects `#herd-ai-host`; ignore it during manual a11y checks.
- Playwright is not used; do not re-add it for CI or local checks.
- Learning reports (v0.6.12): `LEARNING_REPORT_MIN_GROUP_SIZE` (name only; default 5) hides grade distributions for small groups. Certificate PDFs use Dompdf.
- `public/.htaccess` is for Cloudron's Apache; keep PHP settings inside `<IfModule php_module>` and keep the `HTTP_AUTHORIZATION` rule.

## Where to find things

- Doc index: [docs/README.md](docs/README.md)
- Contributing / flow: [CONTRIBUTING.md](CONTRIBUTING.md)
- Local set-up: [docs/local-development.md](docs/local-development.md)
- Deploy: [docs/deploy/cloudron.md](docs/deploy/cloudron.md)
- Release notes: [docs/release-notes.md](docs/release-notes.md)
- Report template: [docs/templates/report.md](docs/templates/report.md)
- Security: [SECURITY.md](SECURITY.md)
