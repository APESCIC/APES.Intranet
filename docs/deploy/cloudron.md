# Deploying to Cloudron

Deploys are run by the owner, the APES IT Administrator, or an agent once the
owner has answered the ship-gate deploy question in the current chat. Every
deploy starts with a Cloudron backup. A merge to `main` never deploys by
itself.

| Item | Value |
| --- | --- |
| App | Cloudron LAMP app |
| App ID | `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264` |
| URL | <https://intranet.apes.org.uk> |
| PHP | 8.4, set by `/app/data/PHP_VERSION`. Do not change it |
| Health check | <https://intranet.apes.org.uk/healthz> |

The commands below run in PowerShell on Windows, with the `cloudron` CLI
signed in.

```powershell
$app = '8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264'
$url = 'https://intranet.apes.org.uk'
```

## Where things live on the app

Everything is under `/app/data`, which Cloudron backs up and keeps across
restarts.

| Path | What |
| --- | --- |
| `/app/data/releases/<id>/` | One folder per release. The newest 5 are kept |
| `/app/data/current` | Symlink to the live release. Apache serves `current/public` |
| `/app/data/shared/.env` | Settings shared by every release (root:www-data, 0640) |
| `/app/data/shared/storage/` | Laravel storage: logs, cache, uploads. Linked into each release |
| `/app/data/run.sh` | Starts the queue worker and the scheduler when the app starts |
| `/app/data/apache/app.conf` | Points Apache at `current/public` |

The database, Redis and mail come from Cloudron addons through the
`CLOUDRON_MYSQL_*`, `CLOUDRON_REDIS_*` and `CLOUDRON_MAIL_*` variables. Do not
copy those values into `.env`.

## First-time set-up

Do this once, before the first deploy.

1. **Settings in the dashboard.** Give the app at least 1 GB of memory. Check
   it is in the backup schedule.
2. **Addons.** Check the variables exist, without printing their values:

   ```powershell
   cloudron exec --app $app -- bash -c 'env | grep -oE "^CLOUDRON_(MYSQL|REDIS|MAIL|LDAP|OIDC)_[A-Z_]+" | sort'
   ```

   If there are no `CLOUDRON_OIDC_*` variables, set up an OIDC client as
   described in [docs/cloudron-sign-in.md](../cloudron-sign-in.md).
3. **Shared folders and `.env`.** Create `$env:TEMP\intranet.env` with only
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

   Then upload it and set the app key. The key is generated on the server and
   never printed:

   ```powershell
   cloudron exec --app $app -- bash -c 'mkdir -p /app/data/shared/storage /app/data/releases'
   cloudron push --app $app "$env:TEMP\intranet.env" /app/data/shared/.env
   cloudron exec --app $app -- bash -c 'k="base64:$(head -c 32 /dev/urandom | base64)"; sed -i "s|^APP_KEY=.*|APP_KEY=$k|" /app/data/shared/.env; chown root:www-data /app/data/shared/.env; chmod 0640 /app/data/shared/.env'
   Remove-Item "$env:TEMP\intranet.env"
   ```

   Copy the `APP_KEY` from the Cloudron File Manager into the APES password
   manager. Without it, encrypted data cannot be read after a restore.
4. **Startup script.**

   ```powershell
   cloudron push --app $app .\deploy\cloudron\run.sh /app/data/run.sh
   cloudron exec --app $app -- bash -c 'chmod 0755 /app/data/run.sh; bash -n /app/data/run.sh'
   ```

Then do a normal deploy, below. The holding page stays up until Apache is
switched over, which follows [holding-page.md](holding-page.md) and needs the
owner's approval on issue #13.

## Each deploy

1. **Check `main`.** The `ci` check on the commit must be green:

   ```powershell
   gh run list --branch main --limit 3
   ```

2. **Back up first.** Note the backup id on the issue:

   ```powershell
   cloudron backup create --app $app
   cloudron backup list --app $app | Select-Object -First 3
   ```

3. **Note the live release**, for a rollback:

   ```powershell
   $previous = cloudron exec --app $app -- bash -c 'readlink -f /app/data/current 2>/dev/null || true'
   ```

4. **Build** the exact commit. This uses a temporary git worktree:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\build-release.ps1 -Ref main
   ```

   Note the printed `ReleaseId`, `ReleaseDir` and `Revision`.
5. **Upload:**

   ```powershell
   cloudron sync push --app $app "<ReleaseDir>\" "/app/data/releases/<ReleaseId>/"
   ```

6. **Activate.** This links `.env` and storage, enters maintenance mode, runs
   migrations, re-syncs roles and permissions, caches config, switches
   `current` and leaves maintenance mode:

   ```powershell
   cloudron exec --app $app -- bash "/app/data/releases/<ReleaseId>/deploy/cloudron/activate-release.sh" "/app/data/releases/<ReleaseId>"
   ```

7. **Restart**, so opcache and `run.sh` pick up the release:

   ```powershell
   cloudron restart --app $app
   ```

8. **Verify.** `/healthz` must return 200 with `"status":"ok"`, and
   `revision` must match the first 12 characters of the deployed commit:

   ```powershell
   curl.exe -s -w "`n%{http_code}`n" "$url/healthz"
   cloudron logs --app $app --lines 100 | Select-String 'queue worker|scheduler'
   ```

9. **If verification fails, roll back the code** and check again:

   ```powershell
   cloudron exec --app $app -- bash "$previous/deploy/cloudron/rollback-release.sh" "$previous"
   cloudron restart --app $app
   ```

   A rollback does not undo migrations. If a migration broke the data, restore
   the backup from step 2 in the Cloudron dashboard.
10. **Record** the release id, revision, backup id and `/healthz` output on the
    issue or pull request.

## Checks after the first deploy

- `/healthz` returns 200, and its output has no hosts, passwords or keys.
- Restart the app, then check `/healthz` again. Signed-in sessions and the
  audit log should still be there, which shows the data survives a restart.
- The logs show "Starting queue worker" and "Starting scheduler". The
  scheduler runs `audit:prune` every night at 03:15.
- Sign in with a Cloudron account in an `intranet.*` group.
