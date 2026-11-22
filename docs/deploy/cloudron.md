# Deploying to Cloudron

Deploys are run by the owner, the APES IT Administrator, or an agent once the
owner has answered the ship-gate deploy question in the current chat. Every
deploy starts with a Cloudron backup. A merge to `main` never deploys by
itself. A deploy always ships a published release tag, never a branch.

| Item | Value |
| --- | --- |
| App | Cloudron LAMP app |
| App ID | `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264` |
| URL | <https://intranet.apes.org.uk> |
| PHP | 8.4, set by `/app/data/PHP_VERSION`. Do not change it |
| Health check | <https://intranet.apes.org.uk/healthz> |

The commands below run as written in Windows PowerShell 5.1 (and in
PowerShell 7), from the repository root, with the `cloudron` CLI signed in
(`cloudron login my.cloudron.apes.org.uk`).

```powershell
$app = '8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264'
$url = 'https://intranet.apes.org.uk'
```

Two Windows PowerShell 5.1 rules apply to every `cloudron exec`:

- Quote the separator: `'--'`. PowerShell 5.1 drops a bare `--` before it
  reaches the `cloudron` CLI.
- Do not pass commands that need `$` or nested double quotes. PowerShell 5.1
  strips the inner quotes. Push a script to `/tmp`, run it, then delete it.

## Where things live on the app

Everything is under `/app/data`, which Cloudron backs up and keeps across
restarts.

| Path | What |
| --- | --- |
| `/app/data/releases/<id>/` | One folder per release. The newest 5 are kept |
| `/app/data/current` | Symlink to the live release. Apache serves `current/public` |
| `/app/data/shared/.env` | Settings shared by every release (mode 0640) |
| `/app/data/shared/storage/` | Laravel storage: logs, cache, uploads. Linked into each release |
| `/app/data/run.sh` | Starts the queue worker and the scheduler when the app starts |
| `/app/data/apache/app.conf` | Points Apache at `current/public` |

The LAMP image runs `chown -R www-data:www-data /app/data` every time the app
starts. So `shared/.env` shows owner `www-data` after a restart, even though
`activate-release.sh` sets `root:www-data`. That is expected. What matters is
mode `0640`.

## Addon settings

The database, Redis, mail and the directory come from Cloudron addons. The
config files read the live `CLOUDRON_*` variable first and only fall back to
`.env` when it is missing (in local development). So a password rotation, a restore or a
clone never leaves the app using an old copy. `run.sh` rebuilds the config
cache on every start, which picks up any changed value.

| Addon | Variables read | Secret |
| --- | --- | --- |
| MySQL | `CLOUDRON_MYSQL_HOST`, `CLOUDRON_MYSQL_PORT`, `CLOUDRON_MYSQL_DATABASE`, `CLOUDRON_MYSQL_USERNAME`, `CLOUDRON_MYSQL_PASSWORD`, `CLOUDRON_MYSQL_URL` | `CLOUDRON_MYSQL_PASSWORD`, `CLOUDRON_MYSQL_URL` |
| Redis | `CLOUDRON_REDIS_HOST`, `CLOUDRON_REDIS_PORT`, `CLOUDRON_REDIS_PASSWORD`, `CLOUDRON_REDIS_URL` | `CLOUDRON_REDIS_PASSWORD`, `CLOUDRON_REDIS_URL` |
| Sendmail | `CLOUDRON_MAIL_SMTP_SERVER`, `CLOUDRON_MAIL_SMTP_PORT`, `CLOUDRON_MAIL_SMTP_USERNAME`, `CLOUDRON_MAIL_SMTP_PASSWORD`, `CLOUDRON_MAIL_FROM`, `CLOUDRON_MAIL_DOMAIN` | `CLOUDRON_MAIL_SMTP_PASSWORD` |
| LDAP | `CLOUDRON_LDAP_HOST`, `CLOUDRON_LDAP_PORT`, `CLOUDRON_LDAP_USERS_BASE_DN`, `CLOUDRON_LDAP_GROUPS_BASE_DN`, `CLOUDRON_LDAP_BIND_DN`, `CLOUDRON_LDAP_BIND_PASSWORD` | `CLOUDRON_LDAP_BIND_PASSWORD` |
| OIDC client (no addon, set by hand) | `OIDC_ISSUER`, `OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET`, `OIDC_REDIRECT_URI` in `shared/.env` | `OIDC_CLIENT_SECRET` |
| Web Push VAPID (no addon, set by hand) | `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` in `shared/.env` | `VAPID_PRIVATE_KEY` |

`CLOUDRON_APP_ORIGIN` sets the app URL. When Redis is present it is used for
the cache, sessions and queues. Without it they use the database.

### Web Push (VAPID) keys

Browser push needs a VAPID key pair. Generate once, store the values only in
the APES Vault, and set them in Cloudron `shared/.env` (private ops issue in
`.github-private`). Never commit key values to this repository. Treat the
private key like `APP_KEY`: do not rotate it casually, or every subscribed
device must opt in again.

```dotenv
VAPID_SUBJECT=mailto:intranet@apes.org.uk
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

Push notifications are queued (`ShouldQueue`). The Cloudron `run.sh` queue
worker must be running so alerts leave the queue. Confirm in the app logs
("Starting queue worker") after a restart. Admins can open **Admin > Push
delivery** for subscription and opt-in counts only — never payloads or full
endpoints. iPhone and iPad users must add the intranet to the Home Screen
before Safari can subscribe (see Notification preferences).

Rules:

- `shared/.env` must not set `DB_PASSWORD`, `REDIS_PASSWORD`, `MAIL_PASSWORD`
  or `LDAP_BIND_PASSWORD`. The [preflight](#preflight) fails if it does, and a
  test fails if `.env.example` does.
- The app was installed with Cloudron user management on, so the
  `CLOUDRON_LDAP_*` variables exist (checked by name on 5 November 2026). The
  preflight fails if they disappear.
- The OIDC values live in `/app/data/shared/.env`, which is part of the app
  backup, so a restore keeps them. A clone on another domain needs its own
  OIDC client with that domain's redirect URI
  ([cloudron-sign-in.md](../cloudron-sign-in.md)), because the client and
  `OIDC_REDIRECT_URI` name `intranet.apes.org.uk`.

To check that a changed addon value is picked up, restart the app and check
`/healthz` and **Admin > System status**. Every check should be `ok`, with no
`.env` change:

```powershell
cloudron restart --app $app
curl.exe -s "$url/healthz"
```

## Preflight

`preflight.ps1` is a read-only check of the app. It prints one `PASS`, `WARN`
or `FAIL` line per check and exits 1 on any `FAIL`. It never prints a value
from `.env` or an addon variable.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\preflight.ps1
```

It checks:

- the CLI sign-in, the run state and the latest backup
- the shared `.env`, its `APP_KEY`, and that it holds no addon passwords
- `run.sh` against the repository copy
- `app.conf`
- the releases and `current`
- the addon variables, OIDC, PHP and `gosu`

Run it when investigating the live app. Production deploys go through GitHub
Actions only and do not require a local preflight.

## First-time set-up

Do this once, before the first deploy. It was done for v0.0.1 on 5 November
2026.

1. **Settings in the dashboard.** Give the app at least 1 GB of memory. Check
   it is in the backup schedule.
2. **Shared folders and `.env`.** Create `$env:TEMP\intranet.env` with only
   these lines. Leave out `DB_*`, `REDIS_*` and `MAIL_*`:

   ```dotenv
   APP_NAME="APES Intranet"
   APP_ENV=production
   APP_KEY=
   APP_DEBUG=false
   APP_URL=https://intranet.apes.org.uk
   APP_LOCALE=en_GB
   APP_FALLBACK_LOCALE=en
   LOG_CHANNEL=stack
   LOG_STACK=daily
   LOG_LEVEL=info
   SESSION_ENCRYPT=true
   SESSION_SECURE_COOKIE=true
   AUDIT_LOG_RETENTION_DAYS=730
   QA_LOGIN_ENABLED=false
   ```

   Then upload it:

   ```powershell
   cloudron exec --app $app '--' mkdir -p /app/data/shared/storage /app/data/releases
   cloudron push --app $app "$env:TEMP\intranet.env" /app/data/shared/.env
   Remove-Item "$env:TEMP\intranet.env"
   ```

3. **App key.** It is generated on the server and never printed. The script
   refuses to replace a key that is already set:

   ```powershell
   cloudron push --app $app .\deploy\cloudron\set-app-key.sh /tmp/set-app-key.sh
   cloudron exec --app $app '--' bash /tmp/set-app-key.sh
   cloudron exec --app $app '--' rm -f /tmp/set-app-key.sh
   ```

   Copy the `APP_KEY` from the Cloudron File Manager into the APES password
   manager. Without it, encrypted data cannot be read after a restore.
4. **Startup script.**

   ```powershell
   cloudron push --app $app .\deploy\cloudron\run.sh /app/data/run.sh
   cloudron exec --app $app '--' chmod 0755 /app/data/run.sh
   cloudron exec --app $app '--' bash -n /app/data/run.sh
   ```

5. **Sign-in.** Set up the OIDC client and groups as described in
   [docs/cloudron-sign-in.md](../cloudron-sign-in.md).
6. **Check.** Run the [preflight](#preflight). Before the first deploy, expect
   `WARN` lines for `app.conf` and for "no current release yet".

Then deploy, below. Apache keeps serving `/app/data/public` until
`/app/data/apache/app.conf` points it at the release, as in
[Serving the app, or the holding page](#serving-the-app-or-the-holding-page).

## Each deploy

A deploy ships a release that already exists: the version PR is merged, and
the release `vX.Y.Z` is published from the merge commit. **GitHub Actions is
the only ship path.** There is no local `deploy.ps1`.

```powershell
gh workflow run deploy-cloudron.yml -f tag=vX.Y.Z
gh run watch --exit-status
```

The workflow (`.github/workflows/deploy-cloudron.yml`):

1. Refuses unless the tag is on `main`, has a published GitHub Release, and
   has a published release note in `changelog/releases/`
   ([docs/release-notes.md](../release-notes.md)).
2. Builds production assets, stamps `REVISION` (full commit SHA) and
   `VERSION` (the full tag with `v`, matching `AppVersion`), and uploads the
   release to `/app/data/releases/<ReleaseId>`.
3. Takes a Cloudron backup, notes the live release, runs
   `activate-release.sh`, and restarts the app.
4. Verifies `/healthz`: 200, `"status":"ok"`, `version` equal to the tag, and
   `revision` equal to the first 12 characters of the tag's commit.
5. On activate, restart or verify failure, rolls back to the earlier release,
   restarts, and fails the job.

Evidence is the Actions run URL plus `/healthz` showing the new version and
revision. Paste both on the version's Epic.

`activate-release.sh` may print a PHP warning that JIT is disabled. It is
harmless. A rollback does not undo migrations; if a migration broke the data,
restore the backup from the Cloudron dashboard.

### Server-side scripts

These run on the app (or are pushed by Actions). They are not a local ship
path:

| Script | Role |
| --- | --- |
| `activate-release.sh` | Link shared `.env`/storage, migrate, switch `current` |
| `rollback-release.sh` | Switch `current` back to a previous release |
| `build-release.ps1` | Optional local reference build that stamps `VERSION`/`REVISION` |
| `preflight.ps1` | Read-only checks when investigating the live app |

## Serving the app, or the holding page

Before the Laravel app went live, the LAMP app's default web root,
`/app/data/public`, held the APES "coming soon" holding page. The switch to
the app was made with v0.0.1 on 5 November 2026, with the owner's approval on
issue #13. The holding page files (`index.php` and an empty `.htaccess`)
stayed in `/app/data/public` as a fallback until 12 November 2026, when they
were removed with the owner's agreement, after backup
`app_8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264_v5.3.1_872bd76f` (#267).

**Never empty `/app/data/public`.** The LAMP image's `/app/code/start.sh`
treats an empty `/app/data/public` as a first install: on the next restart it
copies its default `index.php` back **and overwrites `/app/data/run.sh`** with
a blank placeholder, which stops the queue worker and scheduler (#276). After
the holding page was removed, leave the image's default `index.php` (or any
other non-empty content) in that folder. `deploy/cloudron/preflight.sh` fails
when the folder is empty. The v0.5.3 deploy hit this and rolled back; do not
treat an empty folder as a tidy end state.

`/app/data/apache/app.conf` decides what Apache serves. To serve the app (as
now), install the repository's config and restart:

```powershell
cloudron push --app $app .\deploy\cloudron\apache-app.conf /app/data/apache/app.conf
cloudron restart --app $app
```

Then check that `/healthz` returns 200 with `"status":"ok"`, the home page
shows the APES sign-in page, and `/no-such-page` shows the APES 404 page.

Removing `/app/data/apache/app.conf` makes Apache serve `/app/data/public`
(the image default `index.php`, not the Laravel app), so the site is not the
intranet. Don't do it to take the site offline: use Laravel's maintenance mode
instead, which shows the APES maintenance page:

```powershell
cloudron exec --app $app '--' gosu www-data php8.4 /app/data/current/artisan down --retry=15
cloudron exec --app $app '--' gosu www-data php8.4 /app/data/current/artisan up
```

If the old holding page is ever needed again, its two files are in the backup
named above. Restore them from the Cloudron backup's files rather than
restoring the whole app.

## Troubleshooting

| Symptom | Cause | Fix |
| --- | --- | --- |
| The holding page still shows, and `/robots.txt` returns 404 | `/app/data/apache/app.conf` was not switched, or the app was not restarted after it was | Push `apache-app.conf` and restart, as in [Serving the app, or the holding page](#serving-the-app-or-the-holding-page) |
| `/healthz` returns 404 | Apache is not serving `/app/data/current/public` | Run the preflight. Check the `app.conf` line, and that `current` points at a release with `artisan` |
| A release folder inside a release folder, such as `releases/<id>/apes-intranet-release-<id>/` | The upload had a trailing `\` on the source, or used an older `build-release.ps1` | Delete the outer folder with `cloudron exec --app $app '--' rm -rf /app/data/releases/<id>`. Then upload again with no trailing `\`. The preflight warns about any release with no `artisan` |
| `Create /app/data/shared/.env first.` | First-time set-up step 2 was not done | Do [first-time set-up](#first-time-set-up) steps 2 and 3 |
| `/healthz` `revision` does not match the tag after a deploy | Built from `main` or another ref, not the tag. Or the app was not restarted (opcache). Or activation failed before the switch | Re-run `gh workflow run deploy-cloudron.yml -f tag=vX.Y.Z`. Check `readlink -f /app/data/current`. The workflow rolls back by itself in this case |
| `shared/.env` is owned by `www-data`, not `root` | The LAMP image runs `chown -R www-data:www-data /app/data` on every start | Expected. Mode must be `0640` |
| `activate-release.sh` prints a PHP warning that JIT is disabled | PHP's opcache JIT setting in the LAMP image | Harmless. No action |

## Checks after the first deploy

- `/healthz` returns 200, and its output has no hosts, passwords or keys.
- Restart the app, then check `/healthz` again. Signed-in sessions and the
  audit log should still be there, which shows the data survives a restart.
- The logs show "Starting queue worker" and "Starting scheduler". The
  scheduler runs `system:check` every 5 minutes, `audit:prune` every night at
  03:15 and `access-review:remind` on the first day of each quarter.
- **Admin > System status** shows every addon `OK` and the scheduler `OK`.
  The sendmail check only connects and says `QUIT`, so it sends nothing.
  After 3 failed checks in a row, admins get one email per outage.
- Sign in with a Cloudron account in one of the 8 approved groups.
