# Glossary

Plain-English meanings of the terms used in this repository's docs.

## Git and GitHub

| Term | Meaning |
| --- | --- |
| Repository (repo) | The project's folder of code and its full history, kept on GitHub. |
| Branch | A separate line of work, so changes don't affect other work until they are merged. |
| `main` | The branch that holds released code. Nobody commits to it directly. |
| Version branch | The branch that collects every issue for one version, named `milestone/vX-Y-Z-<slug>`, for example `milestone/v0-5-1-agent-docs-workflow`. |
| Issue branch | A short-lived branch for one issue, named `<type>/<issue>-<slug>`, for example `chore/226-simplify-agent-docs`. |
| Commit | A saved snapshot of changes, with a message saying what changed. |
| Conventional Commit | A commit message that starts with its type, such as `feat:`, `fix:`, `docs:` or `chore:`, and ends with `(Refs #<issue>)`. |
| Pull request (PR) | A request to merge one branch into another, where the changes are reviewed and checked. |
| Draft PR | A PR marked as not ready to merge yet. |
| Merge | Bringing one branch's changes into another. |
| Merge commit | A merge that keeps every commit and adds one commit marking the join. We always merge this way. |
| Squash and rebase | Ways of rewriting history into fewer or reordered commits. We don't use them on shared branches. |
| Merge conflict | When two branches changed the same lines and git can't decide which to keep. A person has to choose. |
| Tag | A permanent label on one commit, such as `v0.5.1`. |
| Release | The GitHub page for a tag, with its notes. |
| Issue | A GitHub item describing one piece of work or one problem. |
| Milestone | A GitHub group of issues for one version, titled with the version, such as `v0.6.0 HR and training`. |
| Epic | The parent issue for a milestone, titled `[Epic] <milestone title>`. The ship-gate answer and deploy evidence go on it. |
| Sub-issue | A child issue linked to a parent issue. |
| Label | A tag on an issue, such as `area:hr`. |
| `gh` | GitHub's command-line tool, used to create issues, comments, PRs and releases. |
| Dependabot | GitHub's bot that proposes updates to the packages the app uses. |
| CODEOWNERS | The file that names who is asked to review changes. |

## Versions and shipping

| Term | Meaning |
| --- | --- |
| Semantic versioning (semver) | Version numbers in the form MAJOR.MINOR.PATCH: MAJOR for breaking changes, MINOR for new features, PATCH for fixes. |
| Next version | The open milestone with the lowest version above the latest tag. `composer milestone:next` prints it. |
| Release note | The JSON file in `changelog/releases/` that people read in the intranet's change log. |
| Ship gate | The question with options a to e that Bambie answers before any merge into `main`, release or deploy. |
| Deploy | Putting a release live on Cloudron. |
| Hotfix | An urgent fix shipped outside the normal version, only with Bambie's written approval on the issue. |
| Rollback | Going back to the previous release when a deploy fails. `deploy.ps1` does it automatically. |
| `/healthz` | The page that reports the live version and commit, for example <https://intranet.apes.org.uk/healthz>. |

## Checks and automation

| Term | Meaning |
| --- | --- |
| CI (continuous integration) | Automatic checks that run on every PR. |
| Workflow | One CI recipe file in `.github/workflows`. See [workflows.md](workflows.md). |
| Check | One pass or fail result shown on a PR. |
| Required check | A check that must pass before a PR can merge into `main` (`ci` and `milestone-version-check`). |
| Pinned action | A workflow step locked to an exact version, so it can't change without us knowing. |
| Least privilege | Giving a workflow only the permissions it needs. |
| Secret | A password, token or key. Kept only in GitHub Actions secrets or on Cloudron, never in the repository. |
| Pint | The tool that formats PHP code. |
| Larastan | The tool that finds type errors in PHP code. |
| Pest | The tool that runs the tests. |
| Accessibility (a11y) check | An automated test that pages work with keyboards and screen readers. |
| Dependency review | A check that blocks new packages with known security problems. |
| CodeQL | GitHub's free code security scanner, switched on in the repository settings. |

## Local set-up

| Term | Meaning |
| --- | --- |
| Laravel Herd | The Windows app that runs the website, its database and a mail catcher on your own computer. It replaced Laragon in November 2026. |
| Live preview | Your local copy of the site at <http://apes-intranet.test>. |
| Vite | The tool that rebuilds styles and scripts as you save, on port 5174. |
| Demo data | Fake people and records for testing, loaded with `php artisan demo:seed`. |
| QA role switcher | The local page at <http://apes-intranet.test/qa> that signs you in as a fake account for any role. |
| Migration | A script that changes the database structure. Ask before running one. |
| Composer and npm | The tools that install PHP and JavaScript packages. |
| Artisan | Laravel's command-line tool, run as `php artisan <command>`. |
| FastCGI | How Herd runs PHP: nginx hands each request to separate `php-cgi` processes, so the website and the command line use the same PHP 8.4. |
| Mail catcher | Herd's local mail server. It keeps every email the site sends, in Herd's Mail window, and never delivers it. |
| `.env` | The local settings file. Never commit it. |

## Cursor

| Term | Meaning |
| --- | --- |
| Agent | The AI assistant in Cursor that does the work. |
| `AGENTS.md` | The instructions file that Cursor and people read first. |
| Rule | A short instruction file in `.cursor/rules` that Cursor loads always or when relevant. |
| Skill | A step-by-step guide in `.cursor/skills` for one job, such as working on an issue. |
| Command | A `/word` typed in Cursor's chat, such as `/next-version`, that runs a skill. |
| AskQuestion | The clickable question Cursor shows when it needs a decision. |
| Cloudron | The server platform that hosts the live intranet. |
