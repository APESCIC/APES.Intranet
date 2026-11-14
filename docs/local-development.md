# Local development with Laravel Herd

Laravel Herd Pro on Windows is the only supported local set-up for APES
Intranet (it replaced Laragon in November 2026). The app is its own Herd site
at <http://apes-intranet.test>, with its own database and Vite port, so it runs
side by side with the other APES projects. Check every change there, with the
demo data loaded, before any Cloudron deploy.

Use fake data only. Never put real HR, student, volunteer or agreement data in
a local database, a fixture or this repository.

## What you need

- [Laravel Herd](https://herd.laravel.com/windows) for Windows, with a Pro
  licence (MySQL and the mail catcher are Pro features).
- PHP 8.4 in Herd (**Settings**, then **PHP**). If it is missing, run
  `herd php:install 8.4`. Herd's global PHP can be newer: the site is pinned
  to 8.4.
- The `ldap` extension on in Herd's PHP 8.4. Run `herd ini 8.4`, change
  `;extension=ldap` to `extension=ldap`, save, then run `herd restart php`.
- A MySQL 8.4 service on port 3306 (**Services** in Herd, or
  `herd services:create mysql --service-version=8.4.13 --port=3306`).
- Node 22 through Herd's nvm. Turn on Node.js in **Settings**, then run
  `herd node:install 22`. Herd's nvm takes over `C:\Program Files\nodejs`, so
  uninstall any standalone Node.js first.

| Part | What runs it | Address |
| --- | --- | --- |
| Web server | Herd's nginx, serving `public/` | <http://apes-intranet.test> |
| PHP | Herd's PHP 8.4, for nginx and the command line | |
| Database | Herd's MySQL 8.4, database `apes_intranet_local`, user `root`, no password | `127.0.0.1:3306` |
| Front end | Vite (`npm run dev`) | Port 5174 |
| Mail | Herd's mail catcher, shown in Herd's Mail window, never sent | `127.0.0.1:2525` |

The full profile is in [herd/profile.md](herd/profile.md). PHP 8.4 matches
Cloudron. PHP 8.3 is still the floor (`composer.json` pins the platform to
8.3, and CI tests on both 8.3 and 8.4). Locally, sessions and the cache use
files and the queue runs straight away (`sync`), so Redis is not needed.

## First run

1. Open Herd, and check its MySQL service is running.
2. Clone the repository wherever you keep your code, for example:

   ```powershell
   cd $HOME\Documents\GitHub
   git clone https://github.com/APESCIC/APES.Intranet.git
   cd APES.Intranet
   ```

3. Run the setup script from the repository root:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\herd-setup.ps1
   ```

   It:
   - checks Herd is running and has PHP 8.4 (with the extensions the app
     needs), Composer, Node 22 and a running MySQL 8.4 service on port 3306,
     and uses them for this session;
   - links the clone as the `apes-intranet` site (`herd link`) and pins it to
     PHP 8.4 (`herd isolate`), so Herd serves <http://apes-intranet.test>;
   - copies `.env.herd.example` to `.env` if you have no `.env`, and warns
     if your `.env` does not match the profile;
   - runs `composer install`, and `npm ci` if `node_modules` is missing;
   - generates `APP_KEY` if it is blank;
   - creates the `apes_intranet_local` database if it is missing, and runs the
     migrations;
   - asks whether to load the demo data. Add `-SeedDemo` or `-NoDemo` to skip
     the question.

   It is safe to run again at any time. It never touches another project's
   site or database. `composer setup` runs it too. `.env` stays on your
   machine: Git ignores it, so never commit it.

4. Start Vite, and leave it running:

   ```powershell
   npm run dev
   ```

5. Open <http://apes-intranet.test/qa> and sign in as a fake account for any
   role.

## Everyday commands

The setup script sets `PATH` for its own run only. For your own commands,
start each PowerShell session from the repository root with Herd's PHP 8.4,
Composer and Node 22 first on `PATH`. Herd's own `php` command runs Herd's
global PHP, which may be newer than 8.4, so use this script:

```powershell
. .\scripts\herd-env.ps1
```

The dot and space at the start matter: they keep the settings in your
terminal. If PowerShell refuses to run scripts, run
`Set-ExecutionPolicy -Scope Process Bypass` first, in the same terminal.
`herd php` and `herd composer` also use the site's PHP 8.4 from inside the
repository folder.

| Task | Command |
| --- | --- |
| Live preview with hot reload | `npm run dev` (or `composer dev`) |
| Load the demo data | `php artisan demo:seed` (or `composer demo:seed`) |
| Empty the database and reload the demo data | `php artisan demo:reset` (or `composer demo:reset`) |
| Set up or repair | `composer setup` |
| Production build of the assets | `npm run build` (or `composer build`) |
| Artisan | `php artisan <command>` |
| Site details, PHP version and services | `herd site:info` |

## Live preview

Before any work, run the live preview gate,
`powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1`. It
loads the latest code and dependencies and reports pending migrations (ask
before running them). When no migrations are pending, it also syncs roles and
permissions as a deploy does, by running the seeders in
`deploy/cloudron/activate-release.sh`. Then it checks Herd and its MySQL
service are running, and that `/healthz` serves the checked-out revision on
PHP 8.4 through FastCGI, and starts Vite.

`npm run dev` starts Vite on port 5174 and writes `public/hot`, so the pages
at <http://apes-intranet.test> load their CSS and JavaScript from Vite. Saving
a CSS or JavaScript file updates the browser straight away. Saving a Blade
template (in `resources/views` or a module's `resources/views`) or a routes
file reloads the page. You never need to rebuild by hand.

`npm run build` writes `public/build` for production. Stop and start
`npm run dev` afterwards, so the preview uses Vite again.

## Demo data

`php artisan demo:seed` fills every main page and list with fake people,
placements, updates, events, handbook chapters, policies and audit entries.
It uses a fixed random seed, so the data is the same on every run and in
every bug report. `php artisan demo:reset` empties the local database,
migrates it and loads the demo data again. Both refuse to run unless
`APP_ENV=local`.

What it contains, and the test logins, are in
[herd/demo-data.md](herd/demo-data.md).

## Test logins

Sign-in is through Cloudron only, so there are no passwords. Locally, the QA
role switcher at <http://apes-intranet.test/qa> signs you in as a fake account
for any role or common stack of roles, such as `qa-admin@example.test` or
`qa-staff-hr@example.test`. It only works when `QA_LOGIN_ENABLED=true` and
`APP_ENV=local`, as in `.env.herd.example`. Anywhere else, every `/qa`
route returns 404. Leave the flag off on Cloudron.

## Running several APES projects side by side

Each APES repository is its own Herd site, with its own:

- `http://<site>.test` address, from `herd link <site>`;
- database on the shared MySQL service;
- Vite port: this project uses 5174, the others use 5173 or their own;
- PHP version, pinned with `herd isolate`, so projects on different PHP
  versions run at the same time.

They all share Herd's nginx on port 80 and MySQL on port 3306. PHP undoes
`putenv()` at the end of each request, so one project's `.env` values, such
as `APP_KEY` and `DB_DATABASE`, do not carry over into another.

## Checks before a pull request

These match the `ci` check on GitHub:

```powershell
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --memory-limit=1G
php artisan test
npm run build
$env:BASE_URL = 'http://apes-intranet.test'; $env:A11Y_BROWSER_CHANNEL = 'msedge'; npm run test:a11y
```

The last line runs the accessibility check (axe and keyboard) against the
preview, using Microsoft Edge. See [accessibility.md](accessibility.md).

Locally, tests use an in-memory SQLite database (set in `phpunit.xml`), so
they never touch `apes_intranet_local`. CI runs the same tests on PHP 8.3 and 8.4
against a MySQL 8.4 database, so a test that passes locally can still fail on a
MySQL-only difference. What each CI check does is in
[workflows.md](workflows.md).

## Mail

Mail is never sent locally. `MAIL_MAILER=smtp` with `MAIL_HOST=127.0.0.1` and
`MAIL_PORT=2525` hands every message to Herd's mail catcher. Open Herd's
**Mail** window to read it, or ask an agent to use the Herd MCP `mail` tool
(for example to find a link in a message). Tests use the `array` mailer, so
they never send anything either.

## Debugging

- The last exception the site threw, with its stack trace: the Herd MCP
  `site` tool's `error` action, or `storage/logs/laravel.log`.
- `dump()` output, queries, logs and slow requests: Herd's **Dumps** window,
  or the Herd MCP `insights` tool.
- Herd's own health: `herd status`.

## Common problems

| Problem | Fix |
| --- | --- |
| The setup script says PHP 8.4 is not installed | Run `herd php:install 8.4`, then run the script again |
| The setup script says `ldap` is missing | Run `herd ini 8.4`, change `;extension=ldap` to `extension=ldap`, then `herd restart php` |
| The setup script says Herd's nvm has no Node 22 | Turn on Node.js in Herd's **Settings**, then run `herd node:install 22` |
| `php -v` is not 8.4 in your own terminal | Run `. .\scripts\herd-env.ps1`, as in [Everyday commands](#everyday-commands) |
| `/healthz` or `preview.ps1` reports another PHP version | Run `herd isolate 8.4 --site=apes-intranet` |
| `apes-intranet.test` does not resolve, or shows a 404 from Herd | Check `herd links` lists `apes-intranet` pointing at your clone. If not, run `scripts\herd-setup.ps1` again |
| Port 80 or 3306 is in use, and Herd or MySQL will not start | Another web server or MySQL is running (for example Laragon). Find it with `Get-NetTCPConnection -State Listen -LocalPort 80,3306`, stop it, then restart Herd |
| Port 5174 is in use | Another Vite server is running, usually this project in another terminal. Stop it. Vite will not move to another port, because the preview expects 5174 |
| `500` error | Use the Herd MCP `site` `error` action, or read `storage/logs/laravel.log`. Check `.env` exists and has an `APP_KEY` |
| `/healthz` says `"redis": "fail"` | Expected locally. The health check always tries Redis, which Cloudron provides, but the Herd set-up does not use it. `app` and `database` should say `ok` |
| Unstyled page or "Vite manifest not found" | Start `npm run dev`. Vite writes `public/hot` once it is ready |
| `herd services:list` says MySQL is stopped, but the site works | Herd loses track of its services after Herd itself restarts. If the database misbehaves, stop and start the service in Herd's **Services** |
| Herd's Node.js settings say you don't use the nvm that ships with Herd | A standalone Node.js, or old `NVM_HOME` and `NVM_SYMLINK` variables, are in the way. Uninstall the standalone Node.js, remove both variables, restart Herd, then click **Install NVM** |
| Database errors | Check Herd's MySQL service is running (`herd services:list`), and that `.env` has `DB_HOST=127.0.0.1` and `DB_DATABASE=apes_intranet_local` |
| `Permission denied` writing to `storage` or `bootstrap/cache` | Close anything holding the file open (another `artisan` run, an editor or an antivirus scan), then try again. If it keeps happening, give your Windows user full control of the clone folder |
| The demo commands refuse to run | Set `APP_ENV=local` in `.env`. They never run anywhere else |
| `git push` or `gh` hangs, or asks for credentials in a strange window | Another local server stack put its own `git` first on `PATH`. Check with `(Get-Command git).Source`: it should be `C:\Program Files\Git\...`. Remove the other stack's folder from `PATH`, or uninstall the stack, since Herd is the only supported set-up |
| An old database such as `apes_intranet` sits next to `apes_intranet_local` | It is left from an earlier set-up. This project only uses `apes_intranet_local` (see `.env`). Ask Bambie before dropping anything, then drop the old one with `herd db:drop`. Never drop another project's database |

## Moving from Laragon

If you used Laragon before November 2026: stop Laragon (it uses the same
ports 80 and 3306), then follow [First run](#first-run). The demo data loads
the same on every run, so nothing is lost by starting with a fresh Herd
database. The old `C:\laragon\www\apes-intranet` junction does no harm, and
you can delete it once Herd works.
