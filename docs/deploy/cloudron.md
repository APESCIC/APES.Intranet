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

`CLOUDRON_APP_ORIGIN` sets the app URL. When Redis is present it is used for
the cache, sessions and queues. Without it they use the database.

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

`deploy.ps1` runs it first.

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

Then deploy, below. The holding page stays up until Apache is switched over,
which follows [holding-page.md](holding-page.md).

## Each deploy

A deploy ships a release that already exists: the milestone pull request is
merged, and the release `vX.Y.Z` is published from the merge commit.

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\deploy.ps1 -Tag vX.Y.Z
```

The build runs Composer with Laragon's newest PHP 8.4 (as on Cloudron) and
Vite with Laragon's Node, and stops if the PHP is not 8.4. Pass `-Php` or
`-Composer` (the path to `composer.phar`) to use a different PHP 8.4 or
Composer.

`deploy.ps1`:

1. Refuses unless the tag exists, is a published GitHub release, the `ci`
   check is green on its commit, and the tag has a published release note in
   `changelog/releases/` ([docs/release-notes.md](../release-notes.md)).
2. Runs the [preflight](#preflight) and stops on any `FAIL`.
3. Takes a Cloudron backup and notes its id.
4. Notes the live release, for a rollback.
5. Builds the tag with `build-release.ps1` and uploads it to
   `/app/data/releases/<ReleaseId>`.
6. Checks the uploaded `REVISION` is the tag's commit, and the uploaded
   `VERSION` is the tag.
7. Runs `activate-release.sh`, then restarts the app.
8. Verifies `/healthz`: 200, `"status":"ok"`, `version` equal to the tag, and
   `revision` equal to the first 12 characters of the tag's commit. Also
   checks that the queue worker and scheduler are running, and that the logs
   show them start.
9. On any failure from step 7 on, rolls back to the earlier release, restarts,
   verifies again, and exits 1.
10. Prints a summary (release id, revision, backup id, `/healthz` output). Paste
    it on the milestone's Epic, which is its ship issue.

Exit code 0 means the release is live and verified. Exit code 1 means nothing
changed, or the app was rolled back. The summary says which.

`activate-release.sh` may print a PHP warning that JIT is disabled. It is
harmless.

To prove the rollback works, `-ForceVerifyFailure` verifies against a
deliberately wrong revision. The new release is activated, then rolled back,
and the script exits 1. Use it only with the owner's agreement, because it
runs the release's migrations.

A rollback does not undo migrations. If a migration broke the data, restore
the backup from the summary in the Cloudron dashboard.

## Deploying by hand

Use this only if `deploy.ps1` cannot run. It does the same steps.

1. **Check the release.** It must be published, and `ci` must be green on its
   commit:

   ```powershell
   $tag = 'vX.Y.Z'
   gh release view $tag
   gh run list --branch main --limit 3
   ```

2. **Preflight:**

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\preflight.ps1
   ```

3. **Back up first.** Note the backup id:

   ```powershell
   cloudron backup create --app $app
   cloudron backup list --app $app
   ```

4. **Note the live release**, for a rollback:

   ```powershell
   $previous = "$(cloudron exec --app $app '--' readlink -f /app/data/current)".Trim()
   ```

5. **Build** the tag, never `main`. This uses its own git worktree in
   `%LOCALAPPDATA%\APES\apes-intranet-build\worktree`, with Composer on
   Laragon's PHP 8.4 and Vite on Laragon's Node. The worktree keeps
   `vendor/` and `node_modules/` between builds, so when the lock files have
   not changed a build takes about 20 seconds instead of about 3 minutes
   (`-NoCache` starts from scratch):

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref $tag
   ```

   Note the printed `ReleaseId`, `ReleaseDir`, `Revision` and `Version`.
   Because `-Ref` is a tag, the build also writes a `VERSION` file holding
   the tag. The footer version pill and `/healthz` read it, so the site shows
   the version that is actually deployed, not the newest tag. Without a
   `VERSION` file the app reports `dev`.
6. **Upload** one archive and unpack it on the app (much faster than
   uploading thousands of files one by one with `cloudron sync push`, which
   `deploy.ps1 -SyncPush` still does if you need it):

   ```powershell
   tar -czf "$env:TEMP\<ReleaseId>.tar.gz" -C "<ReleaseDir>" .
   cloudron push --app $app "$env:TEMP\<ReleaseId>.tar.gz" /app/data/releases/<ReleaseId>.tar.gz
   cloudron exec --app $app '--' mkdir -p /app/data/releases/<ReleaseId>
   cloudron exec --app $app '--' tar -xzf /app/data/releases/<ReleaseId>.tar.gz -C /app/data/releases/<ReleaseId>
   cloudron exec --app $app '--' rm -f /app/data/releases/<ReleaseId>.tar.gz
   cloudron exec --app $app '--' cat /app/data/releases/<ReleaseId>/REVISION
   ```

   The `REVISION` must equal the printed `Revision`, and `VERSION` (same
   folder) must equal the tag.
7. **Activate.** This links `.env` and storage, enters maintenance mode, runs
   migrations, re-syncs roles and permissions, caches config, switches
   `current` and leaves maintenance mode:

   ```powershell
   cloudron exec --app $app '--' bash /app/data/releases/<ReleaseId>/deploy/cloudron/activate-release.sh /app/data/releases/<ReleaseId>
   ```

8. **Restart**, so opcache and `run.sh` pick up the release:

   ```powershell
   cloudron restart --app $app
   ```

9. **Verify.** `/healthz` must return 200 with `"status":"ok"`, and
   `revision` must match the first 12 characters of `Revision`:

   ```powershell
   curl.exe -s -w "`n%{http_code}`n" "$url/healthz"
   cloudron logs --app $app --lines 500 | Select-String 'run\.sh .*Starting' | Select-Object -Last 2
   ```

10. **If verification fails, roll back the code** and check again:

    ```powershell
    cloudron exec --app $app '--' bash "$previous/deploy/cloudron/rollback-release.sh" $previous
    cloudron restart --app $app
    ```

11. **Record** the release id, revision, backup id and `/healthz` output on the
    milestone's Epic.

## Troubleshooting

| Symptom | Cause | Fix |
| --- | --- | --- |
| The holding page still shows, and `/robots.txt` returns 404 | `/app/data/apache/app.conf` was not switched, or the app was not restarted after it was | Follow [holding-page.md](holding-page.md): push `apache-app.conf`, then `cloudron restart --app $app` |
| `/healthz` returns 404 | Apache is not serving `/app/data/current/public` | Run the preflight. Check the `app.conf` line, and that `current` points at a release with `artisan` |
| A release folder inside a release folder, such as `releases/<id>/apes-intranet-release-<id>/` | The upload had a trailing `\` on the source, or used an older `build-release.ps1` | Delete the outer folder with `cloudron exec --app $app '--' rm -rf /app/data/releases/<id>`. Then upload again with no trailing `\`. The preflight warns about any release with no `artisan` |
| `Create /app/data/shared/.env first.` | First-time set-up step 2 was not done | Do [first-time set-up](#first-time-set-up) steps 2 and 3 |
| `/healthz` `revision` does not match the tag after a deploy | Built from `main` or another ref, not the tag. Or the app was not restarted (opcache). Or activation failed before the switch | Build with `-Ref <tag>`, restart, and check `readlink -f /app/data/current`. `deploy.ps1` rolls back by itself in this case |
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
