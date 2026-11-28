# Cloudron sign-in

Staff, volunteers and students sign in with their Cloudron account through
OpenID Connect (OIDC). Groups come from the OIDC `groups` claim. The nightly
directory sync reads the Cloudron read-only API. The intranet does not use
LDAP.

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

The OpenID client **APES Intranet** comes with the Cloudron app install and is
stored on Cloudron. There is no Vault copy and no separate manual creation
step for a normal install. Check it under **Users > OpenID** in the Cloudron
dashboard (not an older "User Directory > OpenID Connect clients" menu).

The intranet prefers `CLOUDRON_OIDC_*` when Cloudron injects them, and falls
back to `OIDC_*` in `shared/.env`. Production needs:

| Setting | Typical value |
| --- | --- |
| Issuer | `https://my.cloudron.apes.org.uk/openid` |
| Client id / secret | From the **APES Intranet** OpenID client on Cloudron |
| Redirect URI | `https://intranet.apes.org.uk/auth/callback` |
| Scopes | `openid,profile,email,groups` |

Cloudron has no end-session (logout) endpoint, so the intranet does not set a
logout URL. **Sign out** ends the intranet session only.

If the client settings need to be written or repaired by hand:

1. Confirm the client under **Users > OpenID**.
2. Write `OIDC_*` lines to a local file under `$env:TEMP` (never git or chat).
3. Copy them into `/app/data/shared/.env` with `set-oidc.sh` and restart, after
   a Cloudron backup and (for an agent) the ship-gate deploy answer in the
   current chat. Commands: [deploy/cloudron.md](deploy/cloudron.md).
4. Check: preflight shows OIDC values set; an `intranet.admin` can sign in;
   a Cloudron user in none of the 8 groups gets "No intranet access".

The Cloudron LAMP package may still inject unused `CLOUDRON_LDAP_*` variables.
The intranet ignores them. Do not enable the PHP `ldap` extension for this app.

## Fresh sign-in for HR and agreements

HR and agreements pages need a Cloudron sign-in from the last
`FRESH_SIGNIN_MINUTES` minutes (default 15), for every role, admin included.
After that the intranet sends the person back to Cloudron with
`prompt=login` and `max_age=0`, and they return to the same page. It does not
depend on how the person signs in (password, passkey or two-factor), only on
Cloudron's `auth_time` claim. Details are in
[architecture.md](architecture.md#fresh-sign-in).

## Locally

The local Herd preview has no Cloudron client. Use the amber **Developer
sign-in** panel under "Sign in with Cloudron" on
<https://apes-intranet.test/login>, then the amber **QA** pill in the menu to
switch role (<https://apes-intranet.test/qa> is the fallback). "Sign in with
Cloudron" shows a "temporarily unavailable" page locally, because the OIDC
values are blank. Pages that need a fresh sign-in ask a QA account to confirm
its current role instead of going through Cloudron.

## Signing out

**Sign out** ends the intranet session only. The Cloudron session stays open,
so the next sign-in may not ask for a password.
