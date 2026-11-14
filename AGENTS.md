# APES Intranet: start here

For people and Cursor agents. Write UK English. Words in *italics* are in the
[glossary](docs/glossary.md).

## What this repo is

The private intranet for APES CIC staff, volunteers and students: updates, the
directory, rotas, clock in and out, tasks, forms and the help desk, with HR,
training, agreements and hiring to come. It is a Laravel app, live at
<https://intranet.apes.org.uk> on *Cloudron*. The owner is Bambie Murphy,
Director. Her decisions are in [docs/decisions.md](docs/decisions.md).

## Start here

1. Install *Laravel Herd* Pro with PHP 8.4 (`ldap` on), a MySQL 8.4 service
   on port 3306 and Node 22 through Herd's nvm (the only supported local
   set-up).
2. Clone the repo, then run
   `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\herd-setup.ps1 -SeedDemo`.
3. Before any work, run the live preview gate:
   `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1`.
4. Open <http://apes-intranet.test/qa> to sign in as a fake account for any role.
5. Stuck? See [docs/local-development.md](docs/local-development.md).

## How we work

Each *milestone* is one version, such as `v0.6.0`, and ships once.

1. **Work.** Each *issue* gets an *issue branch* from the *version branch*,
   *commits* as needed, and a *PR* into the version branch. When it has merged
   and its *checks* pass, the issue gets a "Work complete" comment and is
   closed. Then the next issue starts.
2. **Merge.** When every issue is closed, merge `main` into the version branch
   and open one PR into `main`. After Bambie's *ship gate* answer, it merges
   with one *merge commit* (never squash or rebase).
3. **Release.** One *tag* `vX.Y.Z` on that merge commit, and one GitHub
   *release*.
4. **Deploy.** One Cloudron *deploy* of that tag, with `/healthz` as evidence.
   Then the Epic and the milestone close.

"Complete the next version" means the open milestone with the lowest version
above the latest tag, never GitHub's milestone number. A *hotfix* needs
Bambie's written approval on the issue. The full flow, with a worked example,
is in [CONTRIBUTING.md](CONTRIBUTING.md).

### This repo's flow wins

Where a personal Cursor rule or skill describes a different flow, follow this
repo. In particular:

- One issue branch and one PR per issue into the version branch, not one PR
  for the whole milestone.
- Close each issue by hand after its PR merges. The version PR says
  `Refs #<epic>`, never `Closes`.
- Branches are `milestone/vX-Y-Z-<slug>` and `<type>/<issue>-<slug>`, not
  `cursor/feature/...`.
- Issue comments use the templates in
  [`.cursor/skills/work-on-issue/templates/`](.cursor/skills/work-on-issue/templates/),
  not `Add-IssueUpdate.ps1`. The milestone closes with the `gh api` step in
  the `deploy-version` skill, not `Close-Milestone.ps1`.

## Everyday commands

Run them from the repo root with Herd's PHP 8.4 and Node 22 first on `PATH`
(Herd's global PHP may be newer): run `. .\scripts\herd-env.ps1` once per terminal ([how](docs/local-development.md#everyday-commands)).

| Command | What it does | When |
| --- | --- | --- |
| `/next-version` | Cursor: starts or resumes the next version | Starting a version |
| `/work-on-issue` | Cursor: works one issue from start to close | Each issue |
| `/plan` | Cursor: writes a plan and waits for approval | Before any big task |
| `/status` | Cursor: shows the version, its issues and its PR | Any time |
| `/finish-version` | Cursor: release note, version PR, ship gate, merge | Every issue closed |
| `/release` | Cursor: tag and GitHub release | After the merge |
| `/deploy` | Cursor: Cloudron deploy, evidence, close-out | After the release |
| `. .\scripts\herd-env.ps1` | Puts Herd's PHP 8.4, Composer and Node 22 first on `PATH` | Each new terminal |
| `scripts\preview.ps1` | Loads the latest code and checks the preview | Before any work |
| `scripts\herd-setup.ps1` | Sets up or repairs the Herd site for this repo | First time, or when broken |
| `npm run dev` | Starts Vite hot reload on port 5174 | While working |
| `php artisan demo:seed` | Loads fake demo data | Once, or after a reset |
| `php artisan test` | Runs the tests | Before each PR |
| `php vendor/bin/pint` | Formats PHP code | Before each commit |
| `php vendor/bin/phpstan analyse --memory-limit=1G` | Finds PHP type errors (Larastan) | Before each PR |
| `npm run test:a11y` | Accessibility check (set `BASE_URL` first) | Before each PR |
| `composer milestone:next` | Prints the next version and its names | Starting a version |
| `composer milestone:status -- vX.Y.Z` | Lists a version's open issues | Before closing a milestone |

What each automatic check does, and what to do if one fails: [docs/workflows.md](docs/workflows.md).

## Rules you must follow

- Never commit, print or paste passwords, tokens, keys or `.env` values.
- Fake data only. No real HR, student, volunteer or agreement data anywhere.
- Never commit to `main`, force-push, or rebase a shared branch.
- Ask Bambie with AskQuestion for every decision: best option first, marked
  "(Recommended)", and a "Stop here" option last.
- No merge into `main`, release or deploy without her ship-gate answer in the
  current chat.
- Ask before running migrations, `demo:reset`, or installing any tool.
- Work only in `APESCIC/APES.Intranet`, plus `docs/apes-intranet/` in the
  private `APESCIC/.github-private` for internal-only docs. Don't rename or
  delete milestones or labels, and don't change DNS or Cloudflare.
- Docs live in this repo; anything that must not be public goes in
  `APESCIC/.github-private` `docs/apes-intranet/`. Never hand a document off
  to SharePoint ([where documents live](docs/decisions.md#where-documents-live)).
- Laravel Herd only for local work: no containers, virtual machines or
  `php artisan serve`. The Herd MCP server (`user-herd`) shows the site's
  last error, logs and caught mail.
- Free GitHub features only. Security reviews use tests and the
  `review-security` skill, never Aikido.

## Herd notes

- Herd's own `php` runs its global PHP (8.5 or newer), so dot-source
  `scripts\herd-env.ps1` before any `php`, `composer` or `npm` command.
- After Herd restarts, `herd services:list` can show MySQL as stopped while
  it still answers on port 3306. Trust `scripts\preview.ps1`.
- Herd injects an "Edit with Herd" badge (`#herd-ai-host`) into local pages;
  the accessibility check excludes it.
- Herd's nvm needs `C:\Program Files\nodejs` free: no standalone Node.js.

## Where to find more

- Every doc, by what you want to do: [docs/README.md](docs/README.md).
- The flow, step by step: [CONTRIBUTING.md](CONTRIBUTING.md), and the rule
  [`.cursor/rules/shipping.mdc`](.cursor/rules/shipping.mdc); the skills are in `.cursor/skills/`.
- Decisions (stack, sign-in, features, hosting, roadmap): [docs/decisions.md](docs/decisions.md).
- Local set-up: [docs/local-development.md](docs/local-development.md).
- Deploying: [docs/deploy/cloudron.md](docs/deploy/cloudron.md).
- Release notes: [docs/release-notes.md](docs/release-notes.md).
- Report template for tasks, PRs and releases: [docs/templates/report.md](docs/templates/report.md).
- Security: [SECURITY.md](SECURITY.md).
