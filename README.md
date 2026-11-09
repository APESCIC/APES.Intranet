# APES Intranet

Private intranet for APES CIC staff, volunteers and students. It will hold
updates, the directory, the organisation chart, rotas, clock in/out, tasks,
forms, the help desk, the handbook, training, HR, hiring and agreements.

## Hosting

The app runs on Cloudron as an empty LAMP stack (PHP 8.x).

| Item | Value |
| --- | --- |
| App ID | `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264` |
| Package id | `lamp.cloudronapp.php74` (old name only) |
| Package version | 5.3.1 |
| PHP | 8.4, set by `/app/data/PHP_VERSION` |
| Domain | `https://intranet.apes.org.uk` |

`intranet.apes.org.uk` already points straight at this Cloudron app. The A
and AAAA records are unproxied, and the certificate is a valid Let's Encrypt
certificate. The SharePoint redirect has been removed.

The Laravel app has been live since v0.0.1. Agents do not change DNS or
Cloudflare. They may run Cloudron deploys once the owner has approved the
deploy in the current chat.

Deploys follow [docs/deploy/cloudron.md](docs/deploy/cloudron.md).
Accessibility notes and checks are in [docs/accessibility.md](docs/accessibility.md).
After a deploy, check <https://intranet.apes.org.uk/healthz>.

## Local development with Laragon

Laragon on Windows is the only supported local set-up. APES Intranet is its
own Laragon project at <http://apes-intranet.test> (Apache, PHP 8.4, MySQL
8.4 and Node 22), so it runs side by side with the other APES projects. Check
every change there before any Cloudron deploy.

```powershell
git clone https://github.com/APESCIC/APES.Intranet.git
cd APES.Intranet
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1 -SeedDemo
npm run dev
```

Then open <http://apes-intranet.test/qa> and sign in as a fake account for
any role. The full guide, with the prerequisites, the profile, the demo data,
running several projects side by side and common fixes, is
[docs/local-development.md](docs/local-development.md). The project's
Laragon profile is [docs/laragon/profile.md](docs/laragon/profile.md), and
the demo data and test logins are in
[docs/laragon/demo-data.md](docs/laragon/demo-data.md). See also
[AGENTS.md](AGENTS.md).

## Related sites

- LMS: <https://lms.intranet.apes.org.uk/>
- MyAPES Account: <https://myaccount.myapes.me.uk/>

## Licence

This code is proprietary to Association of Protecting Exotic Species CIC
(APES CIC). It is published for transparency only. No licence is granted to
use, copy, modify, distribute or deploy it, in whole or in part, without prior
written permission from APES CIC. See [LICENSE](LICENSE). For permission or
questions, use the contact details in [SECURITY.md](SECURITY.md).
