# Learning data backup, restore and rollback

**Milestone:** v0.6.13 (Task [#189](https://github.com/APESCIC/APES.Intranet/issues/189)).

Prove that intranet learning data can be restored, and write how to roll back
a bad deploy. This runbook covers the **intranet** Cloudron app only
(`intranet.apes.org.uk`, id `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264`).

**Never** touch Moodle Upskilling (`upskilling.intranet.apes.org.uk`) or
TutorLMS. **Never** delete production backups.

General Cloudron deploy steps: [cloudron.md](cloudron.md).

## What is backed up

A normal Cloudron app backup includes `/app/data` (code releases, storage,
`.env`) and the MySQL addon. Learning tables live in that database
(`learning_*` course, enrolment, progress, grade and certificate tables).

## Preconditions

- Cloudron CLI signed in on this machine (`cloudron list` works)
- A recent healthy backup for the intranet app (`cloudron backup list --app …`)
- Owner approval before any clone, restore, install or uninstall

## Drill: restore onto a throwaway test app

Goal: prove a backup restores learning data without changing production.

1. **List backups** (read-only):

   ```powershell
   $app = '8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264'
   cloudron backup list --app $app
   ```

   Note a recent `normal` backup id (for example the newest from today).

2. **Create a throwaway clone** from that backup onto a free subdomain
   (example location: `intranet-restore-drill.apes.org.uk`). Use the Cloudron
   dashboard **Clone** / CLI clone only after Bambie confirms the location.
   Do not clone Moodle.

3. **Wait** until the clone is `running` / healthy.

4. **Verify learning data** on the clone (read-only exec):

   ```powershell
   # Counts only — no secrets
   cloudron exec --app <clone-id> '--' bash -c 'cd /app/data/current && php artisan tinker --execute="echo Modules\\Learning\\Models\\Course::count();"'
   ```

   Confirm course / enrolment counts look like production (or match what you
   expect from the backup time). Hit the clone’s `/healthz` if DNS is ready.

5. **Record** on issue #189: backup id, clone app id / location, course count,
   health, date.

6. **Remove the clone** after the drill (uninstall), only with approval.
   Do not delete the production backup used for the drill.

## Rollback after a bad intranet deploy

A failed Actions deploy should leave production on the previous release. If a
bad release is live:

### A. Release symlink rollback (code only)

If migrations did **not** break data, switch `current` back to the previous
release directory as in [cloudron.md](cloudron.md) (`rollback-release.sh`).
This does **not** undo migrations.

### B. Full backup restore (data + code)

If learning (or other) data is wrong after migrations:

1. Take a **new** backup of the broken state first (for forensics), if safe.
2. With owner approval, restore the last known-good backup onto the
   **intranet** app only (dashboard or `cloudron restore`).
3. Confirm `/healthz` and spot-check My learning / My training.
4. Never restore onto Moodle as part of this runbook.

## Production deploy note

Normal ships use GitHub Actions (`gh workflow run deploy-cloudron.yml -f tag=…`).
Actions starts with a Cloudron backup. Merge and release do not auto-deploy.

## Drill evidence (v0.6.13)

Ran on 22 Nov 2026 against intranet `8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264`:

| Item | Value |
| --- | --- |
| Source backup | `app_8bd0acdc-ca6d-467e-aa1e-5c9eb52e3264_v5.3.2_50771f8e` |
| Clone location | `intranet-restore-drill.apes.org.uk` |
| Clone id | `0542f8a6-8c4d-4d4b-8810-f6aa574d8614` |
| Clone health | running / healthy; `/healthz` status `ok` (database and redis ok) |
| Learning counts on clone | courses=0, enrolments=0, lesson_progress=0 (schema present; no pilot rows on production yet) |
| Moodle | not touched |
| Cleanup | clone uninstalled after the check; production backups kept |

## Out of scope

- Moodle or TutorLMS backup/restore
- Deleting backups
- Migrating Moodle courses into the intranet
