# Docs

Every guide in this folder, by what you want to do. Start with
[AGENTS.md](../AGENTS.md) if you are new. Terms are in the
[glossary](glossary.md).

## Set up and work

| Doc | What it covers |
| --- | --- |
| [local-development.md](local-development.md) | Laragon set-up, everyday commands, checks before a PR and common problems |
| [laragon/profile.md](laragon/profile.md) | The Laragon profile, why the NTS build and FastCGI, and [profile.json](laragon/profile.json) |
| [laragon/demo-data.md](laragon/demo-data.md) | What the fake demo data contains and the test logins |
| [laragon/apes-intranet.test.conf](laragon/apes-intranet.test.conf) | A reference copy of the virtual host Laragon writes |
| [workflows.md](workflows.md) | Every automatic check on GitHub, and what to do when one fails |
| [templates/report.md](templates/report.md) | The report shape for tasks, PRs and GitHub releases |
| [release-notes.md](release-notes.md) | How to write and publish a release note for the change log |
| [glossary.md](glossary.md) | Plain-English meanings of the terms in these docs |

How work ships is in [CONTRIBUTING.md](../CONTRIBUTING.md).

## Decisions and design

| Doc | What it covers |
| --- | --- |
| [decisions.md](decisions.md) | Bambie's decisions: stack, sign-in, features, hosting, shipping, roadmap |
| [architecture.md](architecture.md) | Core, Modules and Plugins, roles and permissions, fresh sign-in and the audit log |
| [adr/0001-modules-and-integrations.md](adr/0001-modules-and-integrations.md) | Modules, Integrations and the proposed module registry |
| [accessibility.md](accessibility.md) | The WCAG 2.2 AA baseline, the automated check and rules for new pages |
| [dpia/clock-in-geofence.md](dpia/clock-in-geofence.md) | The approved data protection impact assessment for the clock-in geofence |
| [discovery/lms-inventory.md](discovery/lms-inventory.md) | What the two LMS apps hold, for the Learning versions |

## Modules

| Doc | What it covers |
| --- | --- |
| [people/README.md](people/README.md) | Directory, organisation chart, profiles, student placements and 2FA status |
| [people/directory-sync.md](people/directory-sync.md) | The nightly read-only sync from Cloudron LDAP |
| [communications/README.md](communications/README.md) | Dashboard, updates, events, handbook, policies and Microsoft 365 links |
| [communications/admin-menu-mode.md](communications/admin-menu-mode.md) | The Staff and Admin panel switch |
| [communications/microsoft-links-a11y.md](communications/microsoft-links-a11y.md) | Accessibility and mobile notes for the Microsoft 365 links |
| [communications/sharepoint-split.md](communications/sharepoint-split.md) | What lives on the intranet and what stays in SharePoint |
| [operations/README.md](operations/README.md) | Rotas, clock in and out, timesheets, tasks, forms and checklists |
| [helpdesk/README.md](helpdesk/README.md) | Tickets, queues, SLAs, 2FA resets, email and reports |

## Cloudron

| Doc | What it covers |
| --- | --- |
| [deploy/cloudron.md](deploy/cloudron.md) | First-time set-up, each deploy, rollback, the holding page fallback and troubleshooting |
| [cloudron-sign-in.md](cloudron-sign-in.md) | OIDC sign-in, the 8 approved groups and how roles are set |

## Screenshots

[`screenshots/`](screenshots/) holds the app shell on desktop, tablet and
mobile.
