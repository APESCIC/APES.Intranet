# Laragon profile for APES Intranet

APES Intranet is its own Laragon project. These are the values it needs.
[`profile.json`](profile.json) holds the same values for
`scripts/laragon-setup.ps1`, and a test keeps them in step with
`.env.laragon.example` and `vite.config.js`.

| Setting | Value |
| --- | --- |
| Project folder | `C:\laragon\www\apes-intranet` (a junction to your clone) |
| Hostname | `http://apes-intranet.test` (HTTPS works with Laragon's SSL) |
| Document root | `public/` (Laragon's Auto Virtual Hosts finds it) |
| Web server | Apache 2.4 |
| PHP | 8.4, as on Cloudron |
| PHP build | NTS (`php-8.4.*-nts-Win32-vs17-x64`), the same build for Apache and the command line |
| PHP handler | FastCGI (mod_fcgid, `C:\laragon\etc\apache2\fcgid.conf`), with separate PHP processes per site |
| PHP extensions | ctype, curl, dom, fileinfo, intl, ldap, mbstring, openssl, pdo_mysql, pdo_sqlite, tokenizer, zip |
| Database engine | MySQL 8.4, InnoDB, `utf8mb4_unicode_ci` |
| Database | `apes_intranet_local` on `127.0.0.1:3306`, user `root`, no password |
| Node | 22, with npm |
| Vite dev server | Port 5174 (the other APES repositories use 5173) |
| Laragon profile | `apes-intranet` |

PHP 8.3 is still the floor (`composer.json` pins the platform to 8.3, and CI
runs 8.3). Use 8.4 locally so it matches Cloudron.

## The Laragon profile

Laragon's Profiles (Menu, then **Profile**) record which version of each
service Laragon runs: Apache, PHP, MySQL, Node and so on. Laragon keeps each
profile in `C:\laragon\usr\profile\<name>.ini`.

`scripts/laragon-setup.ps1` writes `C:\laragon\usr\profile\apes-intranet.ini`
the first time it runs, using the installed Apache 2.4, PHP 8.4, MySQL 8.4 and
Node 22 folders. It never changes another profile. To use it, choose Menu,
then **Profile**, then **apes-intranet**.

A profile applies to the whole of Laragon, not one site. Laragon runs one PHP
version for Apache at a time. Every APES project that shares Laragon with this
one needs to work on PHP 8.4, or you switch profile when you switch project.
The setup script warns you when Apache is not on PHP 8.4.

## Why the NTS build and FastCGI

With a thread-safe PHP selected, Laragon loads PHP into Apache (mod_php), so
every site shares the same PHP processes and one project's `.env` values can
leak into another. With an NTS PHP selected, Laragon runs PHP through FastCGI
(mod_fcgid), which starts separate `php-cgi.exe` processes for each site. The
first request after Laragon starts takes a few seconds while PHP starts.

Use the same NTS folder on `PATH` for Artisan, Composer and the tests, so the
command line and the website read the same `php.ini`. The setup script checks:

- that Apache includes `fcgid.conf`, not `mod_php.conf`, and runs PHP 8.4;
- that `public/.htaccess` has no `php_value` or `php_flag` line outside
  `<IfModule php_module>` (FastCGI answers those with a 500 error; Cloudron's
  Apache runs PHP as a module and still reads them);
- that the `HTTP_AUTHORIZATION` pass-through rule is still in `.htaccess`;
- whether OPcache is on with `opcache.validate_timestamps=1`. It only warns,
  because turning it on means editing Laragon's own `php.ini`.

`/healthz` adds a `local` block on the Laragon preview only (`APP_ENV=local`)
with the PHP version, the PHP handler (`cgi-fcgi` under FastCGI) and the
checked-out git revision. `scripts/preview.ps1` reads it.

## What the docs confirm

Checked against the Laragon documentation for Laragon 8.6:

- Auto Virtual Hosts: a folder in `C:\laragon\www` becomes
  `http://<folder>.test` after **Reload**, and Laragon writes
  `C:\laragon\etc\apache2\sites-enabled\auto.<folder>.test.conf`
  ([Pretty URLs](https://laragon.org/docs/pretty-urls)).
- The PHP version is chosen from Menu, then **PHP**, then **Version**, and
  applies to all sites ([Multi-Version Services](https://laragon.org/docs/multi-version)).
- Profiles switch the versions of PHP, MySQL, Apache, Node and the rest
  ([Multi-Stack (Profile)](https://laragon.org/docs/profile)).

Seen on a Laragon 8.6.4 install, but not stated in the docs:

- The automatic virtual host points at the project's `public/` folder when it
  has one. [apes-intranet.test.conf](apes-intranet.test.conf) is a copy of what
  Laragon writes.
- Profiles are stored as `C:\laragon\usr\profile\<name>.ini`, with one
  `[service]` section and a `Version=` line for each service.
