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
Laragon's newest PHP 8.4 (`C:\laragon\bin\php\php-8.4.*`), put first on
`PATH` for the session, and every npm command with Laragon's Node 22. Do not
use any other PHP, and do not use `php artisan serve`. The Cloudron release
build uses the same PHP: `deploy/cloudron/deploy.ps1` stops if the PHP is not
8.4. The Cursor rule `.cursor/rules/laragon-local-dev.mdc` says the same for
agents.

Before any change-making work (code, tests, docs or config), follow the
`live-preview-gate` skill with Laragon:

1. Pull the latest changes with `repo-sync`. It merges `origin/main` into the
   milestone branch with a merge commit and never rebases.
2. Check that Laragon is running and `http://apes-intranet.test/login`
   returns 200, and start Vite with `npm run dev` if nothing listens on port
   5174. Starting Laragon, clicking Reload or switching its profile or PHP
   version needs the owner's click: ask with AskQuestion.
3. Open <http://apes-intranet.test> in Cursor's browser
   (`cursor-browser-preview`). Confirm that it loaded, then say "Preview ready,
   starting work".

`composer install` and `npm ci` may run without asking when `vendor/` or
`node_modules/` is missing. Ask before running migrations, `demo:reset` or
installing any system tool.

| Item | Value |
| --- | --- |
| App URL | `http://apes-intranet.test` (Laragon's Auto Virtual Hosts, from the folder name) |
| Project folder | `C:\laragon\www\apes-intranet`, a junction to the clone |
| Web server | Laragon's Apache 2.4 on port 80, document root `public/` |
| PHP | Laragon's PHP 8.4, for Apache and the command line |
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
- Each milestone has one branch, `milestone/<milestone-slug>`, created from
  `origin/main` when work on it starts. This name takes precedence over the
  skills' default `cursor/feature/<epic>-v<x-y-z>-<slug>`. Commit every issue
  in the milestone on that one branch. Do not create per-issue branches. Never
  commit on `main`. After creating or switching branch, call `SetActiveBranch`.
  See [Milestone shipping](#milestone-shipping).
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

## Milestone shipping

Decided by Bambie. Work ships once per milestone, in four stages, in this
order:

1. Complete **all** the issues in the milestone before moving on.
2. Put all the milestone's work on `main` in **one merge**: a merge commit,
   never a squash or a rebase.
3. Release.
4. Deploy.

Agents run a milestone end to end from one Cursor plan, following the
`github-milestone-completion` skill. The plan covers set-up, every issue, the
pull request, the ship gate (merge, release and deploy) and close-out. When a
new chat resumes a milestone, it carries on with the existing plan and does
not make a new one.

- Open **one draft pull request** from the milestone branch into `main` as
  soon as the branch has its first commit. Its title is the Epic's title, and
  its body lists `Closes #<n>` for every issue in the milestone, including the
  Epic. Continuous integration runs the `ci` check on every push.
- Commit and push on the milestone branch whenever needed. No other pull
  request goes into `main`. Nothing is tagged, released or deployed until
  stage 2.
- After the plan is confirmed, work every issue in order without stopping to
  ask between them. Stop and ask only for a failure or blocker that the plan
  does not settle: red checks after one fix attempt, a blocker outside the
  plan, or new scope.

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
below.

### Hotfixes

A fix may reach `main`, or be released or deployed, ahead of its milestone
only after Bambie has approved it through an AskQuestion answer in the
current chat. Record that answer on the issue.

1. Create a `cursor/fix/<issue>-<slug>` branch from `origin/main`.
2. Open its own pull request, merge it after the ship gate with a merge
   commit (`gh pr merge --merge`), and release it as a patch. `main` accepts
   merge commits only: its ruleset and the repository settings turn off
   squash and rebase merges.
3. Merge `main` back into the open milestone branch with `repo-sync`. Never
   rebase or force-push a milestone branch.

The Cursor rule `.cursor/rules/milestone-shipping.mdc` says the same for
Cursor agents.

## Ship flow

Once per milestone, after every issue in it is committed:

1. Publish the release note. The milestone pull request adds
   `changelog/releases/vX.Y.Z.json` for the milestone's full version, written
   by the rules in [docs/release-notes.md](docs/release-notes.md). As the last
   commit on the milestone branch (`chore: release vX.Y.Z`), review it and set
   `"published": true`. CI fails on the tag if the note is missing or not
   published, and `deploy.ps1` refuses to deploy it. To roll a note back, set
   `"published": false` and deploy; never delete a released note's file.
2. Run `repo-sync` on the milestone branch. Mark the draft pull request ready
   (`gh pr ready`) and wait for `ci` to go green
   (`gh pr checks --watch --fail-fast`). Fix failures on the same branch.
3. Check that every open issue in the milestone is closed by the pull request
   (`Closes #<n>`), or has been moved out with the owner's answer.
4. Ask the ship-gate question, following the `ship-gate` skill. First
   summarise the milestone, the pull request, the checks, the merge method, the
   release tag and the deploy target. Then ask with these options, in this
   order, marking one `(Recommended)`: **a. Merge**, **b. Release**,
   **c. Deploy**, **d. Merge, release and deploy**, **e. Stop here**. The
   answer is the owner's approval, and nothing else counts as consent: not a
   confirmed plan, and not "ship it". Post the answer on the Epic.
5. Run only the chosen steps, in order, and verify each one. After each single
   step, ask again with only the steps that remain.
   - **Merge:** `gh pr merge <pr> --merge --delete-branch`. A merge commit,
     never a squash or a rebase.
   - **Release:** tag the merge commit with the milestone's version, for
     example `v0.0.3`, and publish a GitHub release. This repository keeps no
     version files. The tag is the version. The release build stamps it into
     a `VERSION` file, so `/healthz` reports `version` as well as `revision`,
     and the footer pill shows the version that is deployed, never the newest
     tag.
   - **Deploy:** follow the `cloudron-deploy` skill and
     [docs/deploy/cloudron.md](docs/deploy/cloudron.md):
     `deploy/cloudron/deploy.ps1 -Tag vX.Y.Z`. It runs the preflight, takes a
     backup, deploys the release tag (never a branch), verifies `/healthz` and
     rolls back on failure. A green `main` does not deploy itself.
6. Check `https://intranet.apes.org.uk/healthz` and confirm that `version` is
   the tag and `revision` matches the tag's commit. Post the deploy summary on
   the Epic.
7. Close out with the `github-closeout` scripts. Then post a completion
   message in chat with the milestone, the pull request and its merge commit,
   the issues shipped, the release and the deploy evidence.

## Milestone versioning

Every milestone, branch, PR title, release note, tag and release uses the
milestone's version (`v0.4.0`), never GitHub's milestone number. The rule is
[`.cursor/rules/milestone-versioning.mdc`](.cursor/rules/milestone-versioning.mdc).

- `composer milestone:next` prints the next milestone (the lowest open version
  above the latest tag) and its branch name. `composer milestone:pr-title --
  <issue>` prints the PR title.
- `composer milestone:check-title -- "<title>"`, `milestone:check-branch --
  "<branch>"` and `milestone:check-release -- vX.Y.Z "<release title>"` fail
  with the reason. CI runs the title and branch checks on every pull request
  (`.github/workflows/milestone-version-check.yml`).
- Skills: [`start-next-milestone`](.cursor/skills/start-next-milestone/SKILL.md),
  [`milestone-release-titles`](.cursor/skills/milestone-release-titles/SKILL.md)
  and `/next-milestone`. Script: `scripts/milestone-version`.
- New milestone branches carry the version: `milestone/v0-4-0-operations`.
- Optional local check before each push: `git config core.hooksPath .githooks`.

## Cursor skills and tools

Agents in Cursor use these skills. The steps above come from them. If a skill
disagrees with this file, follow this file.

| Step | Skills |
| --- | --- |
| Starting any change | `repo-sync`, `live-preview-gate` (with Laragon), `cursor-browser-preview` |
| Asking for a decision | `decision-prompts` (AskQuestion) |
| Delivering a milestone | `github-milestone-completion`, `github-cli` (its milestone-to-deploy playbook) |
| Milestone versions | `start-next-milestone`, `milestone-release-titles`, `/next-milestone` (this repository) |
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
