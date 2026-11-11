---
name: work-on-issue
description: Work one GitHub issue from start to close in APES Intranet - assign it, post "Work started", work on an issue branch, open a PR into the version branch, post "Progress update" comments, merge when checks pass, post "Work complete" and close the issue. Use for each issue in a version, or when the user says "work on issue", "next issue" or /work-on-issue.
---

# Work on one issue

Follows `.cursor/rules/shipping.mdc`. One issue at a time. Comments use the templates in `templates/`, never `Add-IssueUpdate.ps1` or another personal script; fill them in, write the result to a file in `$env:TEMP` (never in the repo folder) and post it with `gh issue comment <n> --body-file <file>`. Explain anything technical in one plain sentence. Never put a secret in a comment.

## 1. Start (before writing any code)

1. Run the live preview gate (`.cursor/rules/laragon.mdc`) if you haven't on this branch.
2. Read the issue: `gh issue view <n>`. Check it's in the version's milestone. **Stop and ask** if it isn't, or if it's too big for one PR (offer to split it).
3. Assign it if nobody is assigned: `gh issue edit <n> --add-assignee apescic-director`.
4. Create the issue branch from the version branch:
   `git switch <version branch>`, `git pull --ff-only`, then `git switch -c (php scripts/milestone-version branch --issue <n>)`.
5. Post **Work started** from `templates/work-started.md`: the branch, 3 to 5 plan steps, and the checks that will prove it's done.

## 2. Work and progress

1. Make small Conventional Commits ending `(Refs #<n>)`. Run `php vendor/bin/pint`, `php artisan test` and, for PHP changes, Larastan. Check the change in the preview.
2. Push (`git push -u origin HEAD`) and open the PR into the version branch:
   `gh pr create --base <version branch> --title "[vX.Y.Z] <summary> (#<n>)" --body-file "$env:TEMP\report.md"`. The body follows `docs/templates/report.md` and ends `Refs #<n>`.
3. Post **Progress update** from `templates/progress-update.md` at each meaningful point only: the plan changed, the PR opened, CI failed (and what you're doing), CI fixed, blocked (and what you need from Bambie), ready for review. Not on every commit.
4. Watch the checks: `gh pr checks <pr> --watch`. Fix failures on the same branch.
   - **Stop and ask** if checks are still red after one fix attempt, or you're blocked.

## 3. Complete and close

1. When every check is green, merge into the version branch: `gh pr merge <pr> --merge --delete-branch`.
2. Post **Work complete** from `templates/work-complete.md`: what changed, how it was checked (commands and results), the PR link, and anything Bambie needs to know or do.
3. Close it: `gh issue close <n> --reason completed`. GitHub only closes issues by itself on merges into `main`.
4. Confirm: `gh issue view <n> --json state` must show `CLOSED`.
5. Tick the issue in the Epic's checklist. Save the body to a file first, so its line breaks survive: `gh issue view <epic> --json body --jq .body | Out-File -Encoding utf8 "$env:TEMP\epic.md"`. Change `- [ ] #<n>` to `- [x] #<n>` with the file tools, then run `gh issue edit <epic> --body-file "$env:TEMP\epic.md"` and check the result with `gh issue view <epic>`.

## 4. Next

Only now start the next issue. If Bambie approves moving an issue to a later version instead, post a comment saying why, then `gh issue edit <n> --milestone "<later milestone>"`.

## Never

- Start the next issue while this one is open.
- Close the issue before its PR has merged into the version branch with green checks.
- Write code before the "Work started" comment.
