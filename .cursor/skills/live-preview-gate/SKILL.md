---
name: live-preview-gate
description: Run the APES Intranet live preview gate before any code, test, doc, config, install or commit work, and after every branch switch - herd-env, scripts/preview.ps1, open the Herd site in Cursor's browser. Use whenever starting or resuming work on this repo.
---

# Live preview gate

Follows `.cursor/rules/herd.mdc`. Full guide: `docs/local-development.md`.

## Steps

1. Put Herd's PHP 8.4, Composer and Node 22 first on `PATH` for the session (Herd's global PHP may be newer):
   `Set-ExecutionPolicy -Scope Process Bypass; . .\scripts\herd-env.ps1`
2. Run `powershell -NoProfile -ExecutionPolicy Bypass -File scripts\preview.ps1`. It loads the latest code, installs changed dependencies, reports pending migrations, checks `/healthz` shows the checked-out revision on PHP 8.4 through FastCGI, and starts Vite on port 5174.
   - Exit 3 means a decision (uncommitted changes, a diverged branch, a merge conflict): ask with AskQuestion.
   - Exit 1 is a blocker: report its message.
3. Open <https://apes-intranet.test> in Cursor's browser, confirm it loaded, report the URL, the Vite port and a screenshot, then say "Preview ready, starting work".
4. If it won't load after two attempts, stop and ask how to proceed.

## Notes

- Do not use Herd's badge or app "dev" stack for this site.
- `composer install` and `npm ci` may run without asking when `vendor/` or `node_modules/` is missing or a lock file changed.
- On Linux cloud agents with no Herd, say so in one line and continue only for agent-docs / non-preview change sets when the assignment allows it.
