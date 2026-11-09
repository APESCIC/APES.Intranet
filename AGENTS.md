# Agent instructions for APES Intranet

This file is the source of truth for agents working in this repository. If
another doc disagrees with this file, follow this file and fix the other one
in the same change.

The owner is Bambie Murphy, Director, APES CIC. Write UK English
(organisation, behaviour, colour, licence) in the product, docs, issues and
pull requests.

## Ground rules

- Never print, paste or commit passwords, secrets, tokens or keys. Not in
  chat, issues, pull requests, logs or docs.
- Ask the owner for every decision with Cursor's AskQuestion tool, following
  the `decision-prompts` skill: the best option first, ending in
  `(Recommended)`, with the reason in one line; two to five options; a safe
  `Stop here` last; and the prompt ending "Or choose Other to type your own."
  Never list choices as text in chat and wait. Do not ask again about
  something a confirmed plan, a rule or an earlier answer already settles.
- Before creating a milestone or an Epic, get the owner's answer to an
  AskQuestion. For a large batch of new issues, such as a new milestone's
  scope, write a report or Page for her to approve first.
- Work only in `APESCIC/APES.Intranet`. Do not open issues in another
  repository.
- Do not rename or delete labels. The `area:` labels mark the area of work.
  The issue type goes in the title prefix (see
  [Issues and milestones](#issues-and-milestones)).
- Agents may change Cloudron: deploys, first-time set-up, rollbacks and app
  settings. Read-only commands (status, logs, preflight) run freely. A deploy
  needs the owner's answer to the ship-gate deploy question in the current
  chat. Any other change needs an AskQuestion answer with
  `Back up, then <change> (Recommended)` first. Every change starts with a
  Cloudron backup. Never print `.env` values, `APP_KEY` or addon credentials.
- Do not change DNS or Cloudflare from an agent session.
- Security reviews use PHPUnit and the `review-security` skill. Do not use
  Aikido.

## Stack

Decided. Do not swap these without the owner's approval.

- PHP 8.3 or newer, Laravel 13, `spatie/laravel-permission`.
- Tailwind 4, Vite, Lucide, Fraunces and Nunito Sans. The same look and
  sidebar menu as MyAPES Account.
- Sidebar menus: Normal (member) vs Admin mode for the Admin role only.
  See docs/communications/admin-menu-mode.md. Do not mix system-admin items
  into the Normal or Microsoft 365 menu groups.
- Core, then Modules, then Plugins, as in MyAPES Account ADR 0001. Core never
  depends on a Module or a Plugin.
- TipTap, free core extensions only, for every rich-text box. Sanitise the
  output.
- Responsive on desktop, tablet and mobile, and installable as a PWA.

Cloudron runs PHP 8.4, chosen by `/app/data/PHP_VERSION`. Local development
and the release build use Laragon's PHP 8.4 too, and CI tests on 8.3. All
meet the PHP 8.3 floor. Do not pin Cloudron back to 8.3.

## Sign-in

Decided.

- Cloudron OIDC, with LDAP groups, in the same way as MyAPES Account.
- Volunteers and students are Cloudron directory users. Access comes only
  from the 8 approved groups in `config/intranet.php`: `intranet.staff`,
  `intranet.volunteer`, `intranet.student`, `intranet.supervisor`,
  `intranet.management`, `intranet.hr`, `intranet.legal` and
  `intranet.admin`.
- Account types: staff, volunteer, student. Permission roles: supervisor,
  management, HR, legal, admin.

## Decided features

- Rota, built in: templates, open shifts, swaps and approvals.
- Clock in/out by a tap on the web app or PWA. An optional geofence check
  runs at clock-in only. No continuous tracking. A DPIA is required before
  the geofence check is switched on.
- Help desk, built in Laravel, with IT and Management queues.
- Chat stays in Teams. The intranet only links to it.
- HR at full scope, including appraisals and recognition.
- Agreements: register, templates, versions, renewals, and e-signing via
  DocuSeal on Cloudron (free open-source edition). Send links and email, and
  file the signed PDF and audit log in SharePoint. DocuSeal Pro is an
  optional later upgrade, not part of the first release.
- Files stay in SharePoint.

## Hosting

| Item | Value |
| --- | --- |
| App | Cloudron empty LAMP stack (PHP 8.x) |
| App ID | `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264` |
| Package id | `lamp.cloudronapp.php74` (old name only) |
| Package version | 5.3.1 |
| PHP on Cloudron | 8.4, set by `/app/data/PHP_VERSION` |
| Domain | `https://intranet.apes.org.uk` |

The domain already points straight at the Cloudron app. The A and AAAA
records are unproxied. The certificate is a valid Let's Encrypt certificate.
The SharePoint redirect has been removed.

The Laravel app has been live since v0.0.1 (5 November 2026). The old "coming
soon" holding page stays in `/app/data/public` as a fallback
([docs/deploy/holding-page.md](docs/deploy/holding-page.md)). Further DNS
changes, if any are ever needed, are made by the APES IT Administrator with
Bambie's approval.

## Local development (Laragon)

Decided by Bambie on 9 November 2026 (#213): Laragon is the only supported
local set-up, replacing the earlier set-up from #209. This repository is its own Laragon
project, so it runs side by side with the other APES repositories. Always run
and check every change in the Laragon live preview at
<http://apes-intranet.test> before any Cloudron deploy, and test against the
demo data. Never suggest containers, virtual machines or another local server
stack. Full guide: [docs/local-development.md](docs/local-development.md).

- Profile: [docs/laragon/profile.md](docs/laragon/profile.md) (values in
  `docs/laragon/profile.json`, which the setup script reads).
- Set up or repair: `scripts/laragon-setup.ps1`. It links
  `C:\laragon\www\apes-intranet` to the clone, writes the `apes-intranet`
  Laragon profile, creates the `apes_intranet_local` database and migrates.
  It is safe to run twice and only touches this project.
- Demo data: `php artisan demo:seed` loads it and `php artisan demo:reset`
  empties the local database and reloads it (also `composer demo:seed` and
  `composer demo:reset`). Both refuse unless `APP_ENV=local`. Contents and
  test logins: [docs/laragon/demo-data.md](docs/laragon/demo-data.md).
- Live preview: `npm run dev` (or `composer dev`) for Vite hot reload.

Run every PHP, Artisan, Composer, test, Pint and Larastan command with
Laragon's NTS PHP 8.4 (`C:\laragon\bin\php\php-8.4.*-nts-*`), put first on
`PATH` for the session, and every npm command with Laragon's Node 22. That is
the same build Laragon's Apache runs through FastCGI (mod_fcgid), so the
website and the command line read the same `php.ini`. Do not use any other
PHP, and do not use `php artisan serve`. The Cloudron release build uses PHP
8.4 too: `deploy/cloudron/deploy.ps1` stops if the PHP is not 8.4. The Cursor
rule `.cursor/rules/laragon-local-dev.mdc` says the same for agents.

Before any development or coding work (code, tests, docs or config), run the
live preview gate:

1. **Load the latest version and updates.**
   `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1`
   merges `origin/main` into the milestone branch (never a rebase), runs
   `composer install` or `npm ci` when a lock file changed, reports pending
   migrations (ask before running them), and checks that `/healthz` shows the
   checked-out revision.
2. **Start Laragon.** The same script checks that Laragon is running, that the
   site is served through FastCGI on PHP 8.4, and starts Vite on port 5174 if
   nothing listens there. Starting Laragon, clicking Reload or switching its
   profile or PHP version needs the owner's click: ask with AskQuestion.
3. **Open it in Cursor's browser.** Open <http://apes-intranet.test>
   (`cursor-browser-preview`), confirm that it loaded, and say "Preview
   ready, starting work".
4. **If it won't load after two attempts, stop** and ask how to proceed.

Run the gate again after every branch switch.

`composer install` and `npm ci` may run without asking when `vendor/` or
`node_modules/` is missing. Ask before running migrations, `demo:reset` or
installing any system tool.

| Item | Value |
| --- | --- |
| App URL | `http://apes-intranet.test` (Laragon's Auto Virtual Hosts, from the folder name) |
| Project folder | `C:\laragon\www\apes-intranet`, a junction to the clone |
| Web server | Laragon's Apache 2.4 on port 80, document root `public/` |
| PHP | Laragon's NTS PHP 8.4, run by Apache through FastCGI (mod_fcgid), and the same build on the command line |
| Database | Laragon's MySQL 8.4 on port 3306 with InnoDB, database `apes_intranet_local`, user `root`, no password |
| Cache and sessions | Files (`CACHE_STORE=file`, `SESSION_DRIVER=file`), queue `sync` |
| Front end | Vite hot reload on port 5174 (`npm run dev`), unique among the APES repositories |
| Mail | Written to `storage/logs/laravel.log` (`MAIL_MAILER=log`) |
| Env file | Copy `.env.laragon.example` to `.env` (the setup script does this) |

- Local QA role switcher at <http://apes-intranet.test/qa>, with fake
  accounts only. `QA_LOGIN_ENABLED=true` in the Laragon example. Leave it off
  on Cloudron.
- Seeders create fake data only. Never put real HR, student, volunteer or
  agreement data in a local database, a fixture, or this repository.
- Copy `.env.example` for Cloudron-style keys. Leave those values blank.
  Never commit `.env` or any env file other than the two examples.

## Issues and milestones

Follow the `github-issue-types`, `github-work-tracking` and `github-closeout`
skills.

- Assignee: `apescic-director`.
- Every issue has a milestone. Ask before creating a new milestone.
- Title: `[Type] Short title`. The type is one of `Epic`, `Story`, `Task`,
  `Bug`, `Chore` or `Spike`, for example `[Chore] Add a read-only Cloudron
  preflight check`.
- Each milestone has one Epic, titled `[Epic] <milestone title>`. Its body
  keeps a checklist of sub-issues (`- [ ] #123 [Chore] Short title`), and each
  child is linked as a sub-issue. Stories sit under the Epic. Tasks, bugs and
  chores sit under a Story or straight under the Epic.
- The Epic is the milestone's ship issue. The ship-gate answer and the deploy
  evidence are posted on it.
- Post progress with the `github-closeout` scripts, not by hand:
  `Add-IssueUpdate.ps1` for Started, Decision, Blocked, Split and Committed
  comments, and `Update-MilestoneStatus.ps1` to keep the milestone's status
  block current.
- Area labels (do not invent new ones): `area:cloudron`, `area:local-dev`,
  `area:auth`, `area:people`, `area:comms`, `area:operations`,
  `area:helpdesk`, `area:hr`, `area:agreements`, `area:integrations`,
  `area:accessibility`, `area:tests`.
- Issue forms ask for an area and a milestone. After opening, set that area
  label and that milestone on the issue. The form does not apply them itself.
- Work one milestone at a time, in the order below. Only Foundations is in
  progress until the owner says otherwise.
- Each milestone has one branch, `milestone/vX-Y-Z-<slug>` (printed by
  `composer milestone:next`), created from `origin/main` when work on it
  starts. This name takes precedence over the skills' default
  `cursor/feature/<epic>-v<x-y-z>-<slug>`. Commit every issue
  in the milestone on that one branch. Do not create per-issue branches. Never
  commit on `main`. After creating or switching branch, call `SetActiveBranch`.
  See [Shipping and versioning](#shipping-and-versioning).
- Commits include `Refs #<n>` for the issue they work on.

## Milestones

Open, in this order:

1. **v0.1.x Foundations.** Repository, continuous integration, Laragon
   preview, and the Cloudron deploy path (including `/healthz`). Work this
   milestone before the others.
2. **v0.2.x People.** Directory, organisation chart, and profiles for staff,
   volunteers and students, with Cloudron sign-in and roles.
3. **v0.3.x Communications.** Internal updates. Chat stays in Teams, as a
 link only.
   - **v0.3.5 Platform discovery.** Discovery and planning only: the LMS
     inventory ([docs/discovery/lms-inventory.md](docs/discovery/lms-inventory.md))
     and the Modules and Integrations ADR
     ([docs/adr/0001-modules-and-integrations.md](docs/adr/0001-modules-and-integrations.md)).
     It authorises no build work: a registry pilot or a Training Module each
     needs Bambie's separate approval.
4. **v0.4.x Operations.** Rotas, tasks, forms, and clock in/out.
5. **v0.5.x Help desk.** Help desk in Laravel, with IT and Management queues.
6. **v0.6.x HR and training.** Handbook, training, appraisals and
   recognition.
7. **v0.7.x Agreements and legal.** Agreements register, templates, versions,
   renewals, and DocuSeal e-signing.
8. **v0.8.x Hiring and integrations.** Hiring, and links to the LMS, MyAPES
   and the other APES systems.
9. **v1.0.0 Launch.** Go-live checks: secret scan, no real data in git,
   licence chosen, and owner sign-off. The domain already points at Cloudron.

Do not draft Foundations issues until the owner has approved that report.

## Shipping and versioning

Decided by Bambie. Every milestone is named and worked by its version, never
by GitHub's milestone number, and ships once: every issue committed on one
milestone branch, **one merge commit** into `main`, one tag and GitHub
Release, and one Cloudron deploy of that tag. The single rule is
[`.cursor/rules/version-shipping.mdc`](.cursor/rules/version-shipping.mdc):
the next version, the names built from it, the flow, hotfixes and the
version tooling (`composer milestone:next`, `milestone:bump` and the
title, branch and release checks). Agents run it with the
[`complete-next-version`](.cursor/skills/complete-next-version/SKILL.md) and
[`release-version`](.cursor/skills/release-version/SKILL.md) skills, or the
`/complete-next-version` command, from one Cursor plan per milestone. When a
new chat resumes a milestone, it carries on with the existing plan.

After the plan is confirmed, work every issue in order without stopping to
ask between them. Stop and ask only for a failure or blocker that the plan
does not settle: red checks after one fix attempt, a blocker outside the plan,
or new scope. Merge, release and deploy only after the ship-gate answer in the
current chat (`ship-gate` skill); a confirmed plan or "ship it" is not
consent.

### Definition of done

- An issue is done when its acceptance criteria are met and committed on the
  milestone branch. Post the "Committed" comment (`Add-IssueUpdate.ps1 -Kind
  Committed`) and leave the issue open. It closes when the milestone pull
  request merges.
- A milestone is done only after the single merge, the release, a verified
  deploy, and close-out. Close-out posts the deploy evidence, rolls up the
  parents (`Close-IssueTree.ps1`), then closes the Epic and the milestone
  (`Close-Milestone.ps1`).
- Never close an issue because it is committed, its pull request is open, or
  it "looks merged". If a deploy fails, nothing closes. Reopen any issues that
  closed on the merge until the deploy is fixed or rolled back.
- Work that is dropped is closed as not planned, with the reason
  (`Add-IssueUpdate.ps1 -Kind Abandoned`).

### Dependency updates

Decided by Bambie on 5 November 2026 (#123). Dependabot opens one grouped
pull request a week per ecosystem (Composer, npm and GitHub Actions) into the
long-lived `dependencies` branch, never into `main`.

1. Merge Dependabot pull requests into `dependencies` once `ci` is green. They
   are not ship-gated, because nothing reaches `main` from them directly.
2. When work on a milestone starts, merge `origin/dependencies` into the
   milestone branch with a merge commit (`git merge --no-edit
   origin/dependencies`), then push. The updates ship in the milestone's single
   merge.
3. After each milestone ships, merge `origin/main` into `dependencies` so the
   next updates start from the released code.

A security update that cannot wait for the milestone follows the hotfix route
in the rule.

Security checks use only free GitHub features: Dependabot, dependency review
and CodeQL (JavaScript and GitHub Actions; CodeQL has no PHP support, so PHP
is covered by Pint, Larastan and the tests). No paid or third-party scanner.

## Cursor skills and tools

Agents in Cursor use these skills. The steps above come from them. If a skill
disagrees with this file, follow this file.

| Step | Skills |
| --- | --- |
| Starting any change | `repo-sync`, `live-preview-gate` (with Laragon), `cursor-browser-preview` |
| Asking for a decision | `decision-prompts` (AskQuestion) |
| Delivering a milestone | `github-milestone-completion`, `github-cli` (its milestone-to-deploy playbook) |
| Milestone versions and shipping | `complete-next-version`, `release-version`, `/complete-next-version` (this repository) |
| Issues, comments and close-out | `github-issue-types`, `github-work-tracking`, `github-closeout` |
| Continuous integration | `github-actions`, `loop-on-ci`, `fix-ci` |
| Merge, release and deploy | `ship-gate`, `cloudron-deploy` |
| Operating the Cloudron app | `cloudron-ops` (status, logs, backups, env, restarts) |
| Security review | `review-security` and PHPUnit |

Tools:

- `gh` for GitHub: issues, sub-issues, pull requests, checks and releases.
  Write Markdown bodies to a file and pass `--body-file`.
- The `cloudron` CLI, signed in to `my.cloudron.apes.org.uk`, for the app.
- AskQuestion for every decision, and `SetActiveBranch` after a branch
  change.
- Laragon (Apache, PHP 8.4, MySQL 8.4, Node 22) for the local app, its
  tools, its tests, the demo data and the release build.
- Cursor's browser for the live preview.

## Before going public

- Secret scan of the git history is clean.
- No `.env` file is committed, other than `.env.example` and
  `.env.laragon.example`.
- No real HR, student, volunteer or agreement data is in the repository.
- A licence has been chosen.

## Where to find things

- How work ships, for contributors: [CONTRIBUTING.md](CONTRIBUTING.md).
- Deploying to Cloudron: [docs/deploy/cloudron.md](docs/deploy/cloudron.md).
- Release notes and the change log hub: [docs/release-notes.md](docs/release-notes.md).
- Pull request form: `.github/pull_request_template.md`.
- Security: [SECURITY.md](SECURITY.md).
- Conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
- Issue forms: `.github/ISSUE_TEMPLATE`.
- Discussion forms: `.github/DISCUSSION_TEMPLATE`.
- Local development: [docs/local-development.md](docs/local-development.md).
- Laragon profile and demo data: [docs/laragon/](docs/laragon/profile.md).
- Local env: `.env.laragon.example`. Cloudron env keys: `.env.example`.
