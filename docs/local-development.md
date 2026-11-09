# Local development with WampServer

This guide takes a fresh clone to a running preview at
<http://intranet.apes.test>. Check every change there before any Cloudron
deploy.

Use fake data only. Never put real HR, student, volunteer or agreement data in
a local database, a fixture or this repository.

## What you need

- [WampServer](https://www.wampserver.com/) 3.4 or newer (64-bit), with
  Apache 2.4, PHP 8.4 and MySQL 8.4.
- [Composer](https://getcomposer.org/) 2.
- [Node.js](https://nodejs.org/) 22 or newer, with npm.
- Git.

| Part | What runs it | Address |
| --- | --- | --- |
| Web server | WampServer's Apache, serving `public/` | <http://intranet.apes.test> |
| PHP | WampServer's PHP 8.4, for Apache and the command line | |
| Database | WampServer's MySQL 8.4, database `apes_intranet`, user `root`, no password | `127.0.0.1:3306` |
| Front end | Vite (`npm run dev`) | Port 5174 |
| Mail | Written to `storage/logs/laravel.log` | |

PHP 8.4 matches Cloudron. PHP 8.3 is still the floor (`composer.json` pins the
platform to 8.3, and CI runs 8.3). Locally, sessions and the cache use files
and the queue runs straight away (`sync`), so Redis is not needed.

Laragon must be stopped while WampServer runs, because both use ports 80 and
3306. WampServer's MariaDB on port 3307 is not used.

## First run

1. Clone the repository, for example into
   `C:\Users\<you>\Documents\GitHub\APES.Intranet`.
2. Point `intranet.apes.test` at your machine. From an elevated PowerShell:

   ```powershell
   Add-Content -Path C:\Windows\System32\drivers\etc\hosts -Value "127.0.0.1 intranet.apes.test"
   ```

3. In the WampServer tray menu:
   - choose **PHP**, then **Version**, then the newest **8.4**;
   - make sure **MySQL** is on (WampServer's default database);
   - choose **Start All Services**, and wait for the W icon to turn green.
4. Make InnoDB MySQL's default table engine, as on Cloudron. WampServer
   defaults to MyISAM, which cannot hold the app's indexes. In
   `C:\wamp64\bin\mysql\mysql8.4.x\my.ini`, comment out
   `default_storage_engine=MYISAM` and remove the `;` in front of
   `default_storage_engine=InnoDB`. Then restart MySQL from the tray menu.
5. Add a virtual host for the app at the end of
   `C:\wamp64\bin\apache\apache2.4.x\conf\extra\httpd-vhosts.conf`, using your
   own path to the repository, and restart Apache from the tray menu:

   ```apache
   <VirtualHost *:80>
     ServerName intranet.apes.test
     DocumentRoot "c:/users/<you>/documents/github/apes.intranet/public"
     <Directory "c:/users/<you>/documents/github/apes.intranet/public/">
       Options +FollowSymLinks
       AllowOverride All
       Require local
     </Directory>
   </VirtualHost>
   ```

6. Create the database:

   ```powershell
   & C:\wamp64\bin\mysql\mysql8.4.x\bin\mysql.exe -u root -h 127.0.0.1 -e "CREATE DATABASE apes_intranet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   ```

7. From the repository root, put WampServer's PHP first on `PATH` for this
   session, then copy the env file and install everything:

   ```powershell
   $env:Path = 'C:\wamp64\bin\php\php8.4.x;' + $env:Path; $env:XDEBUG_MODE = 'off'
   Copy-Item .env.wamp.example .env
   composer install
   php artisan key:generate
   php artisan migrate --seed
   npm ci
   ```

   `.env` stays on your machine. Git ignores it. Never commit it.

8. Start Vite, and leave it running:

   ```powershell
   npm run dev
   ```

9. Open <http://intranet.apes.test>.

Replace `8.4.x` and `2.4.x` with the versions in your `C:\wamp64\bin` folders,
for example `php8.4.15`.

## Everyday commands

Start each PowerShell session from the repository root with WampServer's PHP
first on `PATH`, so `php` and `composer` use it:

```powershell
$env:Path = 'C:\wamp64\bin\php\php8.4.x;' + $env:Path; $env:XDEBUG_MODE = 'off'
php artisan <command>
composer <command>
npm <command>
npm run dev                                           # Vite with hot reload
```

`XDEBUG_MODE=off` turns off WampServer's Xdebug on the command line, which
makes Composer and the tests much faster. Leave it out when you want to debug.

## Fake data

`php artisan migrate --seed` adds fake people with `@example.test` addresses.
The seeders refuse to run outside the `local` and `testing` environments. You
can run them again safely, because they do not create duplicates.

To start again from an empty database:

```powershell
php artisan migrate:fresh --seed
```

## Preview as each role

The QA role switcher at <http://intranet.apes.test/qa> signs you in as a fake
account for any role, such as `qa-admin@example.test`. It only works when
`QA_LOGIN_ENABLED=true` and `APP_ENV=local`, as in `.env.wamp.example`.
Anywhere else, every `/qa` route returns 404. Leave the flag off on Cloudron.

## Checks before a pull request

These match the `ci` check on GitHub:

```powershell
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --memory-limit=1G
php artisan test
npm run build
$env:BASE_URL = 'http://intranet.apes.test'; $env:A11Y_BROWSER_CHANNEL = 'msedge'; npm run test:a11y
```

The last line runs the accessibility check (axe and keyboard) against the
preview, using Microsoft Edge. See [accessibility.md](accessibility.md).
`npm run build` writes `public/build`. Stop and start `npm run dev` afterwards,
so the preview uses Vite again.

Tests use an in-memory SQLite database, so they never touch `apes_intranet`.
CI runs the same tests on PHP 8.3.

## Mail

Mail is never sent locally. `MAIL_MAILER=log` writes each message to
`storage/logs/laravel.log`.

## Common problems

| Problem | Fix |
| --- | --- |
| `intranet.apes.test` does not resolve | Add the hosts entry from step 2 |
| The W icon stays orange | Another program holds port 80 or 3306, usually Laragon. Stop it, then choose Restart All Services |
| WampServer's localhost page opens instead of the app | Check the virtual host from step 5, then restart Apache |
| `500` error | Read `storage/logs/laravel.log`. Check `.env` exists and has an `APP_KEY` |
| Unstyled page or "Vite manifest not found" | Start `npm run dev`. Vite writes `public/hot` once it is ready |
| `Specified key was too long; max key length is 1000 bytes` | MySQL is creating MyISAM tables. Make InnoDB the default (step 4), drop and recreate `apes_intranet`, and migrate again |
| Database errors | Check the `wampmysqld64` service is running and `DB_HOST=127.0.0.1` in `.env` |
| `php -v` is not 8.4 | Put WampServer's PHP 8.4 first on `PATH`, as in [Everyday commands](#everyday-commands) |
| Port 5174 in use | Stop the other Vite server, or another project using that port |
