# Workflows and automatic checks

What runs by itself on GitHub, when, and what to do if it fails. Terms are in
the [glossary](glossary.md). Nothing here releases or deploys: those are done
by hand after Bambie's ship-gate answer (see [CONTRIBUTING.md](../CONTRIBUTING.md)).

## Workflows (`.github/workflows/`)

| Workflow | When it runs | What it checks | If it fails |
| --- | --- | --- | --- |
| `ci` | PRs into `main`, a version branch or `dependencies`; pushes to `main`; `vX.Y.Z` tags | Markdown style, workflow files, YAML, no stray `.env` files, Pint, Larastan, the asset build, release notes (and on a tag, that its note is published), the tests on PHP 8.3 with MySQL, and the accessibility check. Its `tests-php84` job repeats the tests on PHP 8.4 | Open the failed step in the run. Run the same command locally (see the table below), fix it on the same branch and push |
| `tests-php84` (in `ci.yml`) | Same triggers as `ci` | The tests on PHP 8.4, the version Cloudron runs, with MySQL | Run `php artisan test` with Laragon's PHP 8.4. Not a required check, but fix it before the version PR |
| `milestone-version-check` | PRs into `main`, a version branch or `dependencies` (not Dependabot) | The PR title and branch name carry the right version, never a milestone number | The log says what's wrong and suggests the right title. Fix the title with `gh pr edit <pr> --title "..."`, or rename the branch |
| `dependency-review` | PRs into `main`, a version branch or `dependencies` | No new package with a known high or critical vulnerability | Read the log for the package. Use a fixed version, or ask Bambie before adding it |

`ci` and `milestone-version-check` are required checks: a PR into `main`
can't merge until both pass.

## Run a `ci` step locally

| `ci` step | Local command |
| --- | --- |
| Check Markdown | `npx markdownlint-cli2 "**/*.md" "#vendor" "#node_modules"` |
| Pint | `php vendor/bin/pint --test` (or `php vendor/bin/pint` to fix) |
| Larastan | `php vendor/bin/phpstan analyse --memory-limit=1G` |
| Build assets | `npm run build` |
| Check release notes | `php artisan changelog:validate` |
| Tests | `php artisan test` |
| Accessibility check | `$env:BASE_URL = 'http://apes-intranet.test'; $env:A11Y_BROWSER_CHANNEL = 'msedge'; npm run test:a11y` |

Locally, tests use SQLite; CI uses MySQL 8.4. If a test passes locally but
fails in CI, look for a MySQL-only difference.

## Other automation

| What | What it does | Kept because |
| --- | --- | --- |
| Dependabot (`.github/dependabot.yml`) | Opens one grouped PR a week each for Composer, npm and Actions updates, into `dependencies`. Security updates open against `main`: retarget them to `dependencies` ([decisions](decisions.md#dependency-updates)) | Updates reach `main` only inside a version |
| CodeQL default set-up (repository settings) | Scans JavaScript, TypeScript and the workflows each week | Free security scanning; no file in the repo |
| `CODEOWNERS` | Asks `apescic-director` to review every PR | One owner reviews everything |
| Issue forms (`.github/ISSUE_TEMPLATE/`) | Feature, Chore, Bug and Epic forms; blank issues are off | Every issue starts with its type and an area |
| PR template (`.github/pull_request_template.md`) | The report shape for every PR | Same shape as task reports and releases ([report template](templates/report.md)) |
| Discussion forms (`.github/DISCUSSION_TEMPLATE/`) | Questions, ideas and notes on guides | Keeps questions out of issues |
| Pre-push hook (`.githooks/pre-push`) | Optional: checks the branch name before a push. Turn it on once with `git config core.hooksPath .githooks` | Catches a wrong branch name before CI does |
| `.editorconfig` | UTF-8, LF line endings, 4 spaces (2 in YAML) | Same formatting in every editor |
| `.markdownlint.yaml` | Markdown rules used by CI and editors: long lines and bare URLs allowed | One config for both |

Pint (`vendor/bin/pint`, Laravel's defaults) formats PHP, and Larastan
(`phpstan.neon`) checks it. There are no other linters or formatters.

## Secrets

Workflows use only GitHub's own short-lived token (`github.token`), with
read-only permissions. Any future secret goes in GitHub Actions secrets, with
its value kept in APES Vault, and is never printed.
