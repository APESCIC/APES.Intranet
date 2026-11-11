# Local development with Laragon

Laragon on Windows is the only supported local set-up for APES Intranet. The
app is its own Laragon project at <http://apes-intranet.test>, with its own
folder, database and Vite port, so it runs side by side with the other APES
projects. Check every change there, with the demo data loaded, before any
Cloudron deploy.

Use fake data only. Never put real HR, student, volunteer or agreement data in
a local database, a fixture or this repository.

## What you need

- [Laragon](https://laragon.org/) (Full) 8 or newer, installed in
  `C:\laragon`. It brings Apache 2.4, PHP, MySQL 8.4, Node, Composer and Git.
- PHP 8.4 in Laragon. If Menu, then **PHP**, then **Version** has no 8.4,
  add it with Menu, then **Tools**, then **Quick add**.
- Node 22 in Laragon (Menu, then **Node.js**, then **Version**).

| Part | What runs it | Address |
| --- | --- | --- |
| Web server | Laragon's Apache, serving `public/` | <http://apes-intranet.test> |
| PHP | Laragon's PHP 8.4, for Apache and the command line | |
| Database | Laragon's MySQL 8.4, database `apes_intranet_local`, user `root`, no password | `127.0.0.1:3306` |
| Front end | Vite (`npm run dev`) | Port 5174 |
| Mail | Written to `storage/logs/laravel.log` | |

The full profile is in [laragon/profile.md](laragon/profile.md). PHP 8.4
matches Cloudron. PHP 8.3 is still the floor (`composer.json` pins the
platform to 8.3, and CI runs 8.3). Locally, sessions and the cache use files
and the queue runs straight away (`sync`), so Redis is not needed.

## First run

1. Start Laragon and click **Start All**.
2. Clone the repository wherever you keep your code, for example:

   ```powershell
   cd $HOME\Documents\GitHub
   git clone https://github.com/APESCIC/APES.Intranet.git
   cd APES.Intranet
   ```

   You can also clone it straight into `C:\laragon\www\apes-intranet`. The
   setup script accepts either.

3. Run the setup script from the repository root:

   ```powershell
   powershell -NoProfile -ExecutionPolicy Bypass -File scripts\laragon-setup.ps1
   ```

   It:
   - checks that Laragon has PHP 8.4 (with the extensions the app needs),
     Composer, Node 22 and MySQL 8.4, and uses them for this session;
   - warns if Laragon's Apache runs a different PHP version;
   - links `C:\laragon\www\apes-intranet` to your clone, and reloads Laragon
     so it creates `http://apes-intranet.test`;
   - writes the `apes-intranet` Laragon profile;
   - copies `.env.laragon.example` to `.env` if you have no `.env`, and warns
     if your `.env` does not match the profile;
   - runs `composer install`, and `npm ci` if `node_modules` is missing;
   - generates `APP_KEY` if it is blank;
   - creates the `apes_intranet_local` database if it is missing, and runs the
     migrations;
   - asks whether to load the demo data. Add `-SeedDemo` or `-NoDemo` to skip
     the question.

   It is safe to run again at any time. It never touches another project's
   folder, database, profile or site. `composer setup` runs it too. `.env`
   stays on your machine: Git ignores it, so never commit it.

4. Apply the Laragon profile: Menu, then **Profile**, then **apes-intranet**.
   This sets Laragon to the versions in the profile. If you only ever run
   projects on the same versions, Laragon's default profile works as well.
5. Start Vite, and leave it running:

   ```powershell
   npm run dev
   ```

6. Open <http://apes-intranet.test/qa> and sign in as a fake account for any
   role.

If <http://apes-intranet.test> shows Laragon's own page, click **Reload** in
Laragon.

## Everyday commands

The setup script sets `PATH` for its own run only. For your own commands,
start each PowerShell session from the repository root with Laragon's PHP
8.4, Composer and Node first on `PATH`. Use your version folders from
`C:\laragon\bin`:

```powershell
$env:Path = 'C:\laragon\bin\php\php-8.4.27-1-nts-Win32-vs17-x64;C:\laragon\bin\composer;C:\laragon\bin\nodejs\node-v22;' + $env:Path; $env:XDEBUG_MODE = 'off'
```

Laragon's own terminal (the **Terminal** button) puts Laragon's active PHP,
Composer and Node on `PATH` for you.

| Task | Command |
| --- | --- |
| Live preview with hot reload | `npm run dev` (or `composer dev`) |
| Load the demo data | `php artisan demo:seed` (or `composer demo:seed`) |
| Empty the database and reload the demo data | `php artisan demo:reset` (or `composer demo:reset`) |
| Set up or repair | `composer setup` |
| Production build of the assets | `npm run build` (or `composer build`) |
| Artisan | `php artisan <command>` |

## Live preview

Before any work, run the live preview gate,
`powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1`. It
loads the latest code and dependencies and reports pending migrations (ask
before running them). When no migrations are pending, it also syncs roles and
permissions as a deploy does, by running the seeders in
`deploy/cloudron/activate-release.sh`.

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
[laragon/demo-data.md](laragon/demo-data.md).

## Test logins

Sign-in is through Cloudron only, so there are no passwords. Locally, the QA
role switcher at <http://apes-intranet.test/qa> signs you in as a fake account
for any role or common stack of roles, such as `qa-admin@example.test` or
`qa-staff-hr@example.test`. It only works when `QA_LOGIN_ENABLED=true` and
`APP_ENV=local`, as in `.env.laragon.example`. Anywhere else, every `/qa`
route returns 404. Leave the flag off on Cloudron.

## Running several APES projects side by side

Each APES repository is its own Laragon project, with its own:

- folder in `C:\laragon\www`, and so its own `http://<folder>.test` address;
- database on the shared MySQL server;
- Vite port: this project uses 5174, the others use 5173 or their own.

They all share Laragon's Apache on port 80 and MySQL on port 3306. Apache runs
the NTS PHP through FastCGI, which starts separate PHP processes for each site,
so each project keeps its own `.env` values, such as `APP_KEY` and
`DB_DATABASE`. (If Apache is ever switched back to the thread-safe PHP build,
which shares one process across sites, `bootstrap/app.php` stops Laravel using
`putenv()` so the values still can't leak. Don't switch it back.) Laragon runs
one PHP version for Apache at a time, so every project you run together needs
to work on that version. APES Intranet needs PHP 8.4. If another project needs
a different version, switch profile when you switch project.

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
they never touch `apes_intranet_local`. CI runs the same tests on PHP 8.3
against a MySQL 8.4 database, so a test that passes locally can still fail on a
MySQL-only difference. What each CI check does is in
[workflows.md](workflows.md).

## Mail

Mail is never sent locally. `MAIL_MAILER=log` writes each message to
`storage/logs/laravel.log`.

## Common problems

| Problem | Fix |
| --- | --- |
| The setup script says PHP 8.4 is not installed | Add it with Menu, then **Tools**, then **Quick add**, then run the script again |
| The script warns that Apache runs another PHP version | Choose Menu, then **Profile**, then **apes-intranet**, or Menu, then **PHP**, then **Version**, then 8.4 |
| `php -v` is not 8.4 in your own terminal | Put Laragon's PHP 8.4 first on `PATH`, as in [Everyday commands](#everyday-commands) |
| `apes-intranet.test` does not resolve | Laragon adds it to the Windows hosts file on **Reload**. Run Laragon as administrator once, or add `127.0.0.1 apes-intranet.test` to `C:\Windows\System32\drivers\etc\hosts` |
| Laragon's own page opens instead of the app | Click **Reload** in Laragon, so Apache loads `auto.apes-intranet.test.conf`. [laragon/apes-intranet.test.conf](laragon/apes-intranet.test.conf) shows what it should contain |
| Port 80 or 3306 is in use, and Apache or MySQL will not start | Another web server or MySQL is running. Find it with `Get-NetTCPConnection -State Listen -LocalPort 80,3306`, stop it, then click **Start All** |
| Port 5174 is in use | Another Vite server is running, usually this project in another terminal. Stop it. Vite will not move to another port, because the preview expects 5174 |
| `500` error | Read `storage/logs/laravel.log`. Check `.env` exists and has an `APP_KEY` |
| `/healthz` says `"redis": "fail"` | Expected locally. The health check always tries Redis, which Cloudron provides, but the Laragon set-up does not use it. `app` and `database` should say `ok` |
| Unstyled page or "Vite manifest not found" | Start `npm run dev`. Vite writes `public/hot` once it is ready |
| Database errors | Check MySQL is running in Laragon, and that `.env` has `DB_HOST=127.0.0.1` and `DB_DATABASE=apes_intranet_local` |
| `Permission denied` writing to `storage` or `bootstrap/cache` | Close anything holding the file open (another `artisan` run, an editor or an antivirus scan), then try again. If it keeps happening, give your Windows user full control of the clone folder |
| The demo commands refuse to run | Set `APP_ENV=local` in `.env`. They never run anywhere else |
