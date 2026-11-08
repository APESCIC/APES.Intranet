# Cloudron sign-in

Staff, volunteers and students sign in with their Cloudron account through
OpenID Connect (OIDC). At each sign-in, the intranet reads the person's
groups from Cloudron LDAP and sets their intranet roles to match.

The owner, the APES IT Administrator, or an agent with the owner's approval in
the current chat does the steps below.

## Groups

Create these groups in Cloudron (**Users > Groups**) and add people to them.

**Account types** say who someone is. Everyone has one:

| Cloudron group | Intranet role | Permissions |
| --- | --- | --- |
| `intranet.staff` | staff | `intranet.access` |
| `intranet.volunteer` | volunteer | `intranet.access` |
| `intranet.student` | student | `intranet.access` |

**Permission roles** say what someone can do, on top of an account type:

| Cloudron group | Intranet role | Permissions |
| --- | --- | --- |
| `intranet.supervisor` | supervisor | `team.supervise`: oversee their own team, meaning view it and approve its shifts, swaps and timesheets |
| `intranet.management` | management | `team.supervise`, `team.manage` and `hr.access` (all HR records) |
| `intranet.hr` | HR | `hr.access` |
| `intranet.legal` | legal | `legal.access` |
| `intranet.admin` | admin | Every permission |

Every role also has `intranet.access`. A person can be in more than one group.

Signed-in people can read a plain-English version of this table, with who
can see HR and legal records and how access is audited, at **Support > Who can
see what** (`/access`).

Only these 8 groups grant access. Any other group is ignored, including any
other name that starts with `intranet.`. The list and the mapping live in
`config/intranet.php` (`group_roles`).

At every sign-in:

- Each approved group gives its role.
- A role is removed when the person has left its group.
- A person in none of the 8 groups sees a "No intranet access" page, and any
  roles they had are removed.

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
   - A Cloudron user in none of the 8 approved groups gets the "No intranet access"
     page.

The PHP `ldap` extension must be enabled on the app. The Cloudron LAMP image
includes it.

## Fresh sign-in for HR and agreements

HR and agreements pages need a Cloudron sign-in from the last
`FRESH_SIGNIN_MINUTES` minutes (default 15), for every role, admin included.
After that the intranet sends the person back to Cloudron with
`prompt=login` and `max_age=0`, and they return to the same page. It does not
depend on how the person signs in (password, passkey or two-factor), only on
Cloudron's `auth_time` claim. Details are in
[architecture.md](architecture.md#fresh-sign-in).

## Locally

The local Docker preview has no Cloudron. Use the QA role switcher at
<http://intranet.apes.test:8080/qa> instead. "Sign in with Cloudron" shows a
"temporarily unavailable" page locally, because the OIDC values are blank.

## Signing out

**Sign out** ends the intranet session only. The Cloudron session stays open,
so the next sign-in may not ask for a password.
