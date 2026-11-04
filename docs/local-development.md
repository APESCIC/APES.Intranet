# Local development with Laragon

This guide takes a fresh clone to a running preview at
<http://apes-intranet.test>. Check every change there before any Cloudron
deploy.

Use fake data only. Never put real HR, student, volunteer or agreement data in
a local database, a fixture or this repository.

## What you need

Everything comes with Laragon 8 at `C:\laragon`:

| Tool | Where | Notes |
| --- | --- | --- |
| PHP 8.3 or newer | `C:\laragon\bin\php` | 8.3 is the floor. Cloudron runs 8.4 |
| Composer | `C:\laragon\bin\composer` | |
| Node 22 | `C:\laragon\bin\nodejs\node-v22` | For Vite |
| MySQL | Laragon service | User `root`, no password |
| Redis | Laragon service | Port 6379 |
| Mailpit | Laragon service | Optional, see below |

The app uses the `predis/predis` package for Redis, so PHP does not need the
`redis` extension.

Laragon's PHP version is shared by every Laragon site. Any version from 8.3 up
works. To test on the floor, run the PHP 8.3 CLI from
`C:\laragon\bin\php\php-8.3.*` for Composer and tests.

## First run

1. Clone the repository into `C:\Users\<you>\Documents\GitHub\APES.Intranet`.
2. Link it into Laragon's web root as `apes-intranet`, from PowerShell:

   ```powershell
   New-Item -ItemType Junction -Path C:\laragon\www\apes-intranet -Target C:\Users\<you>\Documents\GitHub\APES.Intranet
   ```

3. Open Laragon and click **Start All**. Laragon creates the
   `apes-intranet.test` virtual host and serves the `public/` folder.
4. Create the database. In Laragon, open **Database** (HeidiSQL), or run:

   ```powershell
   mysql -uroot -e "CREATE DATABASE IF NOT EXISTS apes_intranet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

5. Install dependencies and set up the app, from the repository root:

   ```powershell
   composer install
   Copy-Item .env.laragon.example .env
   php artisan key:generate
   php artisan migrate --seed
   npm ci
   ```

   `.env` stays on your machine. Git ignores it. Never commit it.

6. Start Vite for hot reload, and leave it running:

   ```powershell
   npm run dev
   ```

7. Open <http://apes-intranet.test>. Vite serves the CSS and JavaScript from
   port 5173 while `npm run dev` runs. Without it, run `npm run build` once.

## Fake data

`php artisan migrate --seed` adds fake people with `@example.test` addresses.
The seeders refuse to run outside the `local` and `testing` environments. You
can run them again safely, because they do not create duplicates.

To start again from an empty database:

```powershell
php artisan migrate:fresh --seed
```

## Checks before a pull request

These match the `ci` check on GitHub:

```powershell
vendor\bin\pint --test
vendor\bin\phpstan analyse --memory-limit=1G
php artisan test
npm run build
```

Tests use an in-memory SQLite database, so they never touch `apes_intranet`.
CI runs the same tests against MySQL.

## Mail

`MAIL_MAILER=log` writes mail to `storage/logs/laravel.log`. To see mail in a
browser, set `MAIL_MAILER=smtp` in `.env`, then open Mailpit at
<http://localhost:8025>.

## Common problems

| Problem | Fix |
| --- | --- |
| The site does not load | In Laragon, click **Start All**, then **Apache > Reload** |
| `500` error | Read `storage/logs/laravel.log`. Check `.env` exists and has an `APP_KEY` |
| `Connection refused` for Redis | Start Redis in Laragon. As a stop-gap, set `CACHE_STORE`, `SESSION_DRIVER` and `QUEUE_CONNECTION` to `file`, `file` and `sync` |
| Unstyled page or "Vite manifest not found" | Run `npm run dev`, or `npm run build` |
| Database errors | Check MySQL runs and `DB_*` in `.env` matches the table above |
