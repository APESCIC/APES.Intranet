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

Until the Laravel app is deployed, the APES IT Administrator is putting up a
simple APES "coming soon" holding page on that app. Agents do not change DNS,
Cloudflare or Cloudron.

Deploys are manual and follow [docs/deploy/cloudron.md](docs/deploy/cloudron.md).
After a deploy, check <https://intranet.apes.org.uk/healthz>.

## Local preview

Day-to-day development uses Laragon at <http://apes-intranet.test>. Check
every change there before any Cloudron deploy. Set-up steps are in
[docs/local-development.md](docs/local-development.md). See also
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
