# Local development with Docker

This guide takes a fresh clone to a running preview at
<http://intranet.apes.test:8080>. Check every change there before any Cloudron
deploy.

Use fake data only. Never put real HR, student, volunteer or agreement data in
a local database, a fixture or this repository.

## What you need

- Docker Desktop, with the WSL 2 engine on Windows.
- Git.

Everything else runs in containers defined in `compose.yaml`:

| Service | What it runs | Address |
| --- | --- | --- |
| `web` | Caddy, serving `public/` | <http://intranet.apes.test:8080> |
| `app` | PHP 8.4 FPM, Composer 2 | Internal |
| `node` | Node 22 and the Vite dev server | `intranet.apes.test:5174` |
| `mysql` | MySQL 8.4, database `apes_intranet`, user `root`, no password | Internal |
| `redis` | Redis 7 | Internal |
| `mailpit` | Catches all mail | <http://localhost:8026> |

PHP 8.4 matches Cloudron. PHP 8.3 is still the floor (`composer.json` pins the
platform to 8.3, and CI runs 8.3). The app uses the `predis/predis` package for
Redis, so PHP does not need the `redis` extension.

MySQL and Redis are not published on the host, so they do not clash with any
other local MySQL or Redis. Vite uses port 5174, so it does not clash with
another project's Vite on the usual 5173.

## First run

1. Clone the repository, for example into
   `C:\Users\<you>\Documents\GitHub\APES.Intranet`.
2. Point `intranet.apes.test` at your machine. From an elevated PowerShell:

   ```powershell
   Add-Content -Path C:\Windows\System32\drivers\etc\hosts -Value "127.0.0.1 intranet.apes.test"
   ```

3. From the repository root, copy the env file and start the containers:

   ```powershell
   Copy-Item .env.docker.example .env
   docker compose up -d --build
   ```

   `.env` stays on your machine. Git ignores it. Never commit it.

4. Install PHP packages, create the key and load fake data:

   ```powershell
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --seed
   ```

5. Open <http://intranet.apes.test:8080>. The `node` container runs `npm ci`
   and then Vite, which serves the CSS and JavaScript with hot reload. The first
   start takes a minute or two.

## Everyday commands

Run every PHP, Composer and npm command in the containers, never on the host:

```powershell
docker compose up -d                                  # start
docker compose down                                   # stop (data is kept)
docker compose exec app php artisan <command>
docker compose exec app composer <command>
docker compose exec node npm <command>
docker compose logs -f node                           # Vite output
```

The `node` container keeps its own Linux `node_modules` in a Docker volume. Any
Windows `node_modules` folder on the host is not used.

## Fake data

`php artisan migrate --seed` adds fake people with `@example.test` addresses.
The seeders refuse to run outside the `local` and `testing` environments. You
can run them again safely, because they do not create duplicates.

To start again from an empty database:

```powershell
docker compose exec app php artisan migrate:fresh --seed
```

To remove the database volume completely: `docker compose down -v`.

## Preview as each role

The QA role switcher at <http://intranet.apes.test:8080/qa> signs you in as a
fake account for any role, such as `qa-admin@example.test`. It only works when
`QA_LOGIN_ENABLED=true` and `APP_ENV=local`, as in `.env.docker.example`.
Anywhere else, every `/qa` route returns 404. Leave the flag off on Cloudron.

## Checks before a pull request

These match the `ci` check on GitHub:

```powershell
docker compose exec app vendor/bin/pint --test
docker compose exec app vendor/bin/phpstan analyse --memory-limit=1G
docker compose exec app php artisan test
docker compose exec node npm run build
docker compose run --rm a11y
```

The last line runs the accessibility check (axe and keyboard) against the
preview. See [accessibility.md](accessibility.md).

Tests use an in-memory SQLite database, so they never touch `apes_intranet`.
CI runs the same tests on PHP 8.3.

## Mail

All mail goes to the `mailpit` container. Read it at <http://localhost:8026>.

## Common problems

| Problem | Fix |
| --- | --- |
| `intranet.apes.test` does not resolve | Add the hosts entry from step 2 |
| The site does not load | `docker compose ps` should show every service up. Read `docker compose logs web app` |
| `500` error | Read `storage/logs/laravel.log`. Check `.env` exists and has an `APP_KEY` |
| Unstyled page or "Vite manifest not found" | Read `docker compose logs node`. Vite writes `public/hot` once it is ready |
| Changes do not hot reload | Vite polls for changes over the Windows bind mount; wait a second, or restart with `docker compose restart node` |
| Database errors | Check `docker compose ps mysql` shows `healthy`, and `DB_HOST=mysql` in `.env` |
| Port 8080, 5174 or 8026 in use | Stop whatever holds the port, or change the left-hand port in `compose.yaml` on your machine only |
