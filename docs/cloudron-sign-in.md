# Cloudron sign-in

Staff, volunteers and students sign in with their Cloudron account through
OpenID Connect (OIDC). At each sign-in, the intranet reads the person's
groups from Cloudron LDAP and sets their intranet roles to match.

The owner, the APES IT Administrator, or an agent with the owner's approval in
the current chat does the steps below.

## Groups

Create these groups in Cloudron (**Users > Groups**) and add people to them:

| Cloudron group | Intranet role |
| --- | --- |
| `intranet.staff` | staff |
| `intranet.volunteer` | volunteer |
| `intranet.student` | student |
| `intranet.manager` | manager |
| `intranet.hr` | HR |
| `intranet.legal` | legal |
| `intranet.admin` | admin |

A person can be in more than one group. Groups that do not start with
`intranet.` are ignored. The mapping lives in `config/intranet.php`
(`group_roles`).

At every sign-in:

- Each `intranet.*` group gives its role.
- A role is removed when the person has left its group.
- A person with no `intranet.*` group sees a "No intranet access" page, and
  any roles they had are removed.

Someone who is already signed in keeps their roles until they sign in again.

## OIDC client

The intranet reads `OIDC_*` settings from the shared `.env`, and falls back to
`CLOUDRON_OIDC_*` variables. It reads groups through the `CLOUDRON_LDAP_*`
variables. The LAMP app has an LDAP addon but no OIDC addon, so it never gets
`CLOUDRON_OIDC_*` variables. Production needs an OIDC client, created once by
hand.

1. **Create the client** in the Cloudron dashboard (**User Directory > OpenID
   Connect clients**):

   | Field | Value |
   | --- | --- |
   | Name | APES Intranet |
   | Login redirect URI | `https://intranet.apes.org.uk/auth/callback` |
   | Token signature algorithm | RS256 |

2. **Write the settings to a local file**, `$env:TEMP\intranet-oidc.env`, and
   never to git or chat. Use these four lines, filling in the client id and
   secret from the dashboard:

   ```dotenv
   OIDC_ISSUER=https://my.cloudron.apes.org.uk/openid
   OIDC_CLIENT_ID=
   OIDC_CLIENT_SECRET=
   OIDC_REDIRECT_URI=https://intranet.apes.org.uk/auth/callback
   ```

   `OIDC_ISSUER` is the Cloudron dashboard URL followed by `/openid`. It must
   match the `issuer` in
   <https://my.cloudron.apes.org.uk/openid/.well-known/openid-configuration>.
3. **Copy them into `/app/data/shared/.env`** and restart, so `run.sh`
   re-caches the config. `set-oidc.sh` accepts only `OIDC_*` lines, replaces
   any old ones, prints key names only, and deletes the uploaded file. Use
   `$app` from [deploy/cloudron.md](deploy/cloudron.md). This changes
   production, so it needs a Cloudron backup first and, for an agent, the
   owner's ship-gate deploy answer in the current chat:

   ```powershell
   cloudron backup create --app $app
   cloudron push --app $app "$env:TEMP\intranet-oidc.env" /tmp/intranet-oidc.env
   cloudron push --app $app .\deploy\cloudron\set-oidc.sh /tmp/set-oidc.sh
   cloudron exec --app $app '--' bash /tmp/set-oidc.sh /tmp/intranet-oidc.env
   cloudron exec --app $app '--' rm -f /tmp/set-oidc.sh
   Remove-Item "$env:TEMP\intranet-oidc.env"
   cloudron restart --app $app
   ```

4. **Check.**
   - Run the preflight
     ([deploy/cloudron.md](deploy/cloudron.md#preflight)). It should show
     `PASS OIDC issuer, client id and client secret are set`.
   - An `intranet.admin` member signs in at <https://intranet.apes.org.uk>
     and opens **Admin > Audit log**.
   - A Cloudron user in no `intranet.*` group gets the "No intranet access"
     page.

The PHP `ldap` extension must be enabled on the app. The Cloudron LAMP image
includes it.

## Locally

Laragon has no Cloudron. Use the QA role switcher at
<http://apes-intranet.test/qa> instead. "Sign in with Cloudron" shows a
"temporarily unavailable" page locally, because the OIDC values are blank.

## Signing out

**Sign out** ends the intranet session only. The Cloudron session stays open,
so the next sign-in may not ask for a password.
