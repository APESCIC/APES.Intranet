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
- Before creating milestones, epics or issues, write a report or Page for the
  owner to approve.
- End every report with next options and one recommended option.
- Work only in `APESCIC/APES.Intranet`. Do not open issues in another
  repository.
- Do not rename or delete labels. The `area:` labels mark the area of work.
  GitHub's default labels may mark type only.
- Do not change DNS, Cloudflare or Cloudron from an agent session.

## Stack

Decided. Do not swap these without the owner's approval.

- PHP 8.3 or newer, Laravel 13, `spatie/laravel-permission`.
- Tailwind 4, Vite, Lucide, Fraunces and Nunito Sans. The same look and
  sidebar menu as MyAPES Account.
- Core, then Modules, then Plugins, as in MyAPES Account ADR 0001. Core never
  depends on a Module or a Plugin.
- TipTap, free core extensions only, for every rich-text box. Sanitise the
  output.
- Responsive on desktop, tablet and mobile, and installable as a PWA.

Cloudron runs PHP 8.4, chosen by `/app/data/PHP_VERSION`. Local Laragon uses
PHP 8.3. Both meet the PHP 8.3 floor. Do not pin Cloudron back to 8.3.

## Sign-in

Decided.

- Cloudron OIDC, with LDAP groups, in the same way as MyAPES Account.
- Volunteers and students are Cloudron directory users in `intranet.*` groups.
- Roles: staff, volunteer, student, manager, HR, legal, admin.

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

Until the Laravel app is deployed, the APES IT Administrator is putting up a
simple APES "coming soon" holding page. Further DNS changes, if any are ever
needed, are made by the APES IT Administrator with Bambie's approval.

## Local development

Always run and check every change in the Laragon live preview at
<http://apes-intranet.test> before any Cloudron deploy.

| Item | Value |
| --- | --- |
| App URL | `http://apes-intranet.test` |
| PHP | Laragon PHP 8.3 |
| Database | MySQL, database `apes_intranet` (matches Cloudron) |
| Cache | Redis when it is available (`CACHE_STORE=redis`) |
| Front end | Node, with Vite hot reload |
| Env file | Copy `.env.laragon.example` to `.env` |

- Local QA role switcher, with fake accounts only. `QA_LOGIN_ENABLED=true`
  in the Laragon example. Leave it off on Cloudron.
- Seeders create fake data only. Never put real HR, student, volunteer or
  agreement data in a local database, a fixture, or this repository.
- Copy `.env.example` for Cloudron-style keys. Leave those values blank.
  Never commit `.env` or any env file other than the two examples.

## Issues and milestones

- Assignee: `apescic-director`.
- Title prefix `[v0.N.x] NN`, with a two-digit index. `00` is the epic. The
  epic body keeps a checklist of sub-issues (`- [ ] #123 Short title`), and
  each child is linked as a sub-issue.
- Area labels (do not invent new ones): `area:cloudron`, `area:local-dev`,
  `area:auth`, `area:people`, `area:comms`, `area:operations`,
  `area:helpdesk`, `area:hr`, `area:agreements`, `area:integrations`,
  `area:accessibility`, `area:tests`.
- Issue forms ask for an area and a milestone. After opening, set that area
  label and that milestone on the issue. The form does not apply them itself.
- Work one milestone at a time, in the order below. Only Foundations is in
  progress until the owner says otherwise.
- Each milestone has one branch, `milestone/<milestone-slug>`, created from
  `main` when work on it starts. Commit issue work on that branch, or on a
  `cursor/{feature|fix|chore}/<issue>-<slug>` branch off it. Never commit on
  `main`. See [Milestone shipping](#milestone-shipping).
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

Commit and push on the milestone branch whenever needed. Nothing reaches
`main`, and nothing is tagged, released or deployed, until stage 2.

Hotfixes: a fix may reach `main`, or be released or deployed, ahead of its
milestone only with Bambie's written approval on the issue.

Definition of done:

- An issue is done when its acceptance criteria are met and committed on its
  branch. Close it then, with a link to the commit.
- A milestone is done only after the single merge, the release and the deploy.

The Cursor rule `.cursor/rules/milestone-shipping.mdc` says the same for
Cursor agents.

## Ship flow

Once per milestone, after every other issue in it is closed:

1. Open one pull request from the milestone branch into `main`. Continuous
   integration runs the `ci` check.
2. Wait until `ci` is green and Bambie has recorded her review on the
   milestone's ship issue.
3. Merge with a merge commit. The owner merges. Agents do not merge unless the
   owner has said to.
4. Release once.
5. Deploy to Cloudron by hand, following
   [docs/deploy/cloudron.md](docs/deploy/cloudron.md). A green `main` does not
   deploy itself.
6. Check `https://intranet.apes.org.uk/healthz` after the deploy.

## Before going public

- Secret scan of the git history is clean.
- No `.env` file is committed, other than `.env.example` and
  `.env.laragon.example`.
- No real HR, student, volunteer or agreement data is in the repository.
- A licence has been chosen.

## Where to find things

- Security: [SECURITY.md](SECURITY.md).
- Conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).
- Issue forms: `.github/ISSUE_TEMPLATE`.
- Discussion forms: `.github/DISCUSSION_TEMPLATE`.
- Local env: `.env.laragon.example`. Cloudron env keys: `.env.example`.
