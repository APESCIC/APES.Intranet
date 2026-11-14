# Herd profile for APES Intranet

APES Intranet is its own Laravel Herd site. These are the values it needs.
[`profile.json`](profile.json) holds the same values for
`scripts/herd-setup.ps1` and `scripts/preview.ps1`, and a test keeps them in
step with `.env.herd.example` and `vite.config.js`.

| Setting | Value |
| --- | --- |
| Herd | Laravel Herd Pro 2.1 or newer, for Windows |
| Site | `apes-intranet`, linked to your clone with `herd link apes-intranet` |
| Hostname | `http://apes-intranet.test` (plain HTTP, as in `APP_URL`) |
| Document root | `public/` (Herd's Laravel driver finds it) |
| Web server | Herd's nginx |
| PHP | 8.4, as on Cloudron. The site is pinned to it (`herd isolate 8.4`), whatever Herd's global PHP is |
| PHP handler | FastCGI (`php-cgi.exe` behind nginx) |
| PHP extensions | ctype, curl, dom, fileinfo, intl, ldap, mbstring, openssl, pdo_mysql, pdo_sqlite, tokenizer, zip |
| Database engine | Herd's MySQL 8.4 service, InnoDB, `utf8mb4_unicode_ci` |
| Database | `apes_intranet_local` on `127.0.0.1:3306`, user `root`, no password |
| Mail | Herd's mail catcher, SMTP on `127.0.0.1` port 2525. Nothing is sent |
| Node | 22, through Herd's nvm |
| Vite dev server | Port 5174 (the other APES repositories use 5173) |

PHP 8.3 is still the floor (`composer.json` pins the platform to 8.3, and CI
tests on 8.3 and 8.4). Use 8.4 locally so it matches Cloudron.

## Where Herd keeps things

Herd keeps its PHP builds, Composer, services and site links under
`%USERPROFILE%\.config\herd`:

| What | Where |
| --- | --- |
| PHP 8.4 | `bin\php84\php.exe`, with its own `php.ini` |
| Composer | `bin\composer.phar` (and `composer.bat`) |
| MySQL 8.4 tools | `bin\services\mysql\8.4.*\bin` |
| Node | Herd's nvm, under `bin\nvm` |
| Site links | `config\valet\Sites` |

`scripts/herd-env.ps1` puts PHP 8.4, Composer and Node 22 first on `PATH`, so
Artisan, Composer and the tests use the same PHP and `php.ini` as the website.

## PHP extensions

Herd's PHP 8.4 ships `php_ldap.dll` but leaves it off. Turn it on once: open
the `php.ini` with `herd ini 8.4`, change `;extension=ldap` to
`extension=ldap`, then run `herd restart php`. The app only talks to LDAP on
Cloudron, and checks for the extension first, but keeping it on locally
matches Cloudron. `scripts/herd-setup.ps1` stops if any extension in the
profile is missing.

## FastCGI and `.env` values

Herd runs one pool of `php-cgi.exe` processes for each PHP version, shared by
every site on that version. PHP undoes `putenv()` at the end of each request,
so one project's `.env` values do not carry over into another.
`bootstrap/app.php` still turns `putenv()` off if PHP ever runs as an Apache
module on Windows.

`public/.htaccess` is not used by nginx, but Cloudron's Apache reads it. Keep
it free of `php_value` and `php_flag` lines outside `<IfModule php_module>`,
and keep the `HTTP_AUTHORIZATION` rewrite rule. A test checks both.

`/healthz` adds a `local` block on the Herd preview only (`APP_ENV=local`)
with the PHP version, the PHP handler (`cgi-fcgi` under FastCGI) and the
checked-out git revision. `scripts/preview.ps1` reads it.

## Running several APES projects side by side

Each APES repository is its own Herd site, with its own `http://<site>.test`
address, its own database on the shared MySQL service, and its own Vite port.
Each site can be pinned to its own PHP version, so projects on different PHP
versions run at the same time.
