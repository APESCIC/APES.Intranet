# Cloudron sign-in

Staff, volunteers and students sign in with their Cloudron account through
OpenID Connect (OIDC). At each sign-in, the intranet reads the person's
groups from Cloudron LDAP and sets their intranet roles to match.

Agents do not change Cloudron. The owner or the APES IT Administrator does the
steps below.

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

The intranet reads the `CLOUDRON_OIDC_*` and `CLOUDRON_LDAP_*` variables that
Cloudron gives the app. If the LAMP app does not provide the OIDC variables,
create a client in the Cloudron dashboard (**User Directory > OpenID Connect
clients**):

| Field | Value |
| --- | --- |
| Name | APES Intranet |
| Login redirect URI | `https://intranet.apes.org.uk/auth/callback` |

Then put its values in `/app/data/.env` on the app, never in git:

```dotenv
OIDC_ISSUER=
OIDC_CLIENT_ID=
OIDC_CLIENT_SECRET=
OIDC_REDIRECT_URI=https://intranet.apes.org.uk/auth/callback
```

`OIDC_ISSUER` is the Cloudron dashboard URL followed by `/openid`. The
`OIDC_*` values override the Cloudron ones.

The PHP `ldap` extension must be enabled on the app. The Cloudron LAMP image
includes it.

## Locally

Laragon has no Cloudron. Use the QA role switcher at
<http://apes-intranet.test/qa> instead. "Sign in with Cloudron" shows a
"temporarily unavailable" page locally, because the OIDC values are blank.

## Signing out

**Sign out** ends the intranet session only. The Cloudron session stays open,
so the next sign-in may not ask for a password.
