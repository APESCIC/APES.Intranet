# Decisions

The decisions behind APES Intranet, made by Bambie Murphy, Director, APES CIC.
Do not change any of them without her approval. Terms are explained in the
[glossary](glossary.md).

## Stack

- PHP 8.3 or newer, Laravel 13, `spatie/laravel-permission`.
- Tailwind 4, Vite, Lucide, Fraunces and Nunito Sans. The same look and
  sidebar menu as MyAPES Account.
- Sidebar menus: the Staff (member) panel, and the Admin panel for the Admin
  role only, decided by the route (`admin.*` is Admin). See
  [admin-menu-mode.md](communications/admin-menu-mode.md). Do not mix
  system-admin items into the Staff or Microsoft 365 menu groups.
- Core, then Modules, then Plugins, as in MyAPES Account ADR 0001. Core never
  depends on a Module or a Plugin. See [architecture.md](architecture.md).
- TipTap, free core extensions only, for every rich-text box. Sanitise the
  output.
- Responsive on desktop, tablet and mobile, and installable as a PWA.
- PHP versions: Cloudron runs PHP 8.4, chosen by `/app/data/PHP_VERSION`.
  Local development and the release build use Laragon's PHP 8.4 too, and CI
  tests on 8.3 and 8.4. All meet the PHP 8.3 floor. Do not pin Cloudron back to 8.3.

## Sign-in

- Cloudron OIDC, with LDAP groups, in the same way as MyAPES Account. See
  [cloudron-sign-in.md](cloudron-sign-in.md).
- Volunteers and students are Cloudron directory users. Access comes only
  from the 8 approved groups in `config/intranet.php`: `intranet.staff`,
  `intranet.volunteer`, `intranet.student`, `intranet.supervisor`,
  `intranet.management`, `intranet.hr`, `intranet.legal` and
  `intranet.admin`.
- Account types: staff, volunteer, student. Permission roles: supervisor,
  management, HR, legal, admin.

## Features

- Rota, built in: templates, open shifts, swaps and approvals.
- Clock in/out by a tap on the web app or PWA. An optional geofence check
  runs at clock-in only. No continuous tracking. A DPIA is required before
  the geofence check is switched on ([DPIA](dpia/clock-in-geofence.md)).
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

- The domain points straight at the Cloudron app. The A and AAAA records are
  unproxied, and the certificate is a valid Let's Encrypt certificate. The
  SharePoint redirect has been removed.
- The Laravel app has been live since v0.0.1 (5 November 2026). The old
  "coming soon" holding page stays in `/app/data/public` as a fallback
  ([holding-page.md](deploy/holding-page.md)).
- Agents do not change DNS or Cloudflare. Any DNS change is made by the APES
  IT Administrator with Bambie's approval.
- Agents may change Cloudron: deploys, first-time set-up, rollbacks and app
  settings. Read-only commands (status, logs, preflight) run freely. A deploy
  needs the ship-gate answer in the current chat. Any other change needs an
  answer to `Back up, then <change> (Recommended)` first. Every change starts
  with a Cloudron backup. Never print `.env` values, `APP_KEY` or addon
  credentials. How to deploy: [deploy/cloudron.md](deploy/cloudron.md).

## Local development

Decided on 9 November 2026 (#213): Laragon is the only supported local
set-up, replacing the earlier set-ups from #209 and before. The repository is
its own Laragon project at <http://apes-intranet.test>: Apache running Laragon's
NTS PHP 8.4 through FastCGI, MySQL 8.4 and Node 22. Never suggest containers,
virtual machines or another local server stack, and never use
`php artisan serve` locally. The full guide is
[local-development.md](local-development.md).

## Shipping

- Every milestone is named and worked by its version (`vMAJOR.MINOR.PATCH`),
  never by GitHub's milestone number, and ships once: one merge commit into
  `main`, one tag and GitHub Release, and one Cloudron deploy of that tag.
- Inside a version (decided 11 November 2026, #227): each issue gets its own
  branch from the version branch and a pull request into it. The issue closes
  when that pull request has merged and its checks pass. The Epic and the
  milestone stay open until the deploy is verified.
- The version branch keeps the name `milestone/vX-Y-Z-<slug>`.
- A hotfix ships early only with Bambie's written approval on the issue.
- The steps are in [CONTRIBUTING.md](../CONTRIBUTING.md) and the rule
  [`.cursor/rules/shipping.mdc`](../.cursor/rules/shipping.mdc).

## Dependency updates

Decided on 5 November 2026 (#123). Dependabot opens one grouped pull request a
week per ecosystem (Composer, npm and GitHub Actions) into the long-lived
`dependencies` branch, never into `main`.

1. Merge Dependabot pull requests into `dependencies` once `ci` is green. They
   are not ship-gated, because nothing reaches `main` from them directly.
2. When work on a version starts, merge `origin/dependencies` into the
   version branch with a merge commit, then push.
3. After each version ships, merge `origin/main` into `dependencies`.

Dependabot security updates are switched on (checked 11 November 2026, #245).
GitHub always opens them against `main`, because `target-branch` only applies
to the weekly updates. When one arrives:

- Retarget it to `dependencies` with `gh pr edit <pr> --base dependencies`,
  then treat it like any other Dependabot PR. It ships inside the next version.
- If it cannot wait for the next version, it follows the hotfix route instead,
  with Bambie's written approval on an issue.
- Never merge a Dependabot PR straight into `main`.

## Security tools

Only free GitHub features: Dependabot, dependency review and CodeQL default
set-up (JavaScript, TypeScript and GitHub Actions; CodeQL has no PHP support,
so PHP is covered by Pint, Larastan and the tests). No paid or third-party
scanner. Security reviews use PHPUnit (Pest) tests and the `review-security`
skill. Do not use Aikido.

## Issues and milestones

- Assignee: `apescic-director`.
- Every issue has a milestone. Ask before creating a milestone or an Epic.
  For a large batch of new issues, write a report for Bambie to approve first.
- Titles start with the type: `[Epic]`, `[Story]`, `[Task]`, `[Bug]`,
  `[Chore]` or `[Spike]`.
- Each milestone has one Epic, titled `[Epic] <milestone title>`, with a
  checklist of its sub-issues. Stories sit under the Epic. Tasks, bugs and
  chores sit under a Story or straight under the Epic. The ship-gate answer
  and the deploy evidence are posted on the Epic.
- Area labels (do not invent new ones or rename them): `area:cloudron`,
  `area:local-dev`, `area:auth`, `area:people`, `area:comms`,
  `area:operations`, `area:helpdesk`, `area:hr`, `area:agreements`,
  `area:integrations`, `area:accessibility`, `area:tests`.
- Work only in `APESCIC/APES.Intranet`.

## Roadmap

Released, closed milestones keep their historical titles (v0.0.1 to v0.0.3,
v0.1.x Foundations, v0.2.x People, v0.3.x Communications and the v0.3.5 to
v0.3.12 platform releases). Open, in this order:

1. **v0.6.0 HR and training.** Handbook, training, appraisals and recognition.
2. **v0.6.5 to v0.6.9 Learning.** Modules registry foundation; learning core
   (courses, enrolment and progress); assessment and gradebook; learning
   records, certificates and reporting; new-course pilot and replacement
   readiness. Each builds on the [LMS inventory](discovery/lms-inventory.md)
   and [ADR 0001](adr/0001-modules-and-integrations.md).
3. **v0.7.0 Agreements and legal.** Agreements register, templates, versions,
   renewals, and DocuSeal e-signing.
4. **v0.8.0 Hiring and integrations.** Hiring, and links to the LMS, MyAPES
   and the other APES systems.
5. **v1.0.0 Launch.** Go-live checks (below) and owner sign-off.

## Go-live checks

The repository is already public. Check these again before v1.0.0:

- Secret scan of the git history is clean.
- No `.env` file is committed, other than `.env.example` and
  `.env.laragon.example`.
- No real HR, student, volunteer or agreement data is in the repository.
- Done: a licence has been chosen (proprietary, see [LICENSE](../LICENSE)).

## Data

Seeders create fake data only. Never put real HR, student, volunteer or
agreement data in a local database, a fixture or this repository. Never
commit passwords, secrets, tokens or keys.
