# APES Intranet

Private intranet for APES CIC staff, volunteers and students. It holds
updates, events, the directory and organisation chart, rotas, clock in and
out, timesheets, tasks, checklists, forms, the help desk and the handbook, with
HR, training, agreements and hiring to come.

It is a Laravel app, live at <https://intranet.apes.org.uk> on Cloudron.
Sign-in is through Cloudron.

## Quick start

Laragon on Windows is the only supported local set-up.

```powershell
git clone https://github.com/APESCIC/APES.Intranet.git
cd APES.Intranet
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1 -SeedDemo
npm run dev
```

Then open <http://apes-intranet.test/qa> and sign in as a fake account for any
role.

## Links

- Start here, for people and agents: [AGENTS.md](AGENTS.md)
- How work ships: [CONTRIBUTING.md](CONTRIBUTING.md)
- Local set-up in full: [docs/local-development.md](docs/local-development.md)
- Decisions: [docs/decisions.md](docs/decisions.md)
- Deploying to Cloudron: [docs/deploy/cloudron.md](docs/deploy/cloudron.md)
- Accessibility: [docs/accessibility.md](docs/accessibility.md)
- Glossary: [docs/glossary.md](docs/glossary.md)
- Related sites: [LMS](https://lms.intranet.apes.org.uk/) and
  [MyAPES Account](https://myaccount.myapes.me.uk/)

## Licence

This code is proprietary to Association of Protecting Exotic Species CIC
(APES CIC). It is published for transparency only. No licence is granted to
use, copy, modify, distribute or deploy it, in whole or in part, without prior
written permission from APES CIC. See [LICENSE](LICENSE). For permission or
questions, use the contact details on <https://github.com/APESCIC> or the APES
CIC website. Report security problems privately, as in
[SECURITY.md](SECURITY.md).
