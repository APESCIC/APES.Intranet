---
name: finish-version
description: Finish a version of APES Intranet once every issue is closed - write and publish the release note, merge main into the version branch, open the version PR into main, get CI green, ask the ship gate and merge once with a merge commit. Use when composer milestone:status passes, or when the user says "finish the version", "merge the milestone" or /finish-version.
---

# Finish a version (merge)

Follows `.cursor/rules/shipping.mdc`. Then `release-version` and `deploy-version`.

## Steps

1. **Check every issue is closed.** `composer milestone:status -- vX.Y.Z`. **Stop** if it fails: list the open issues and work them with `work-on-issue`, or ask Bambie about moving them.
2. **Sync.** `git switch <version branch>`, `git pull --ff-only`, run the live preview gate (it merges `origin/main` in).
3. **Release note.** `composer milestone:bump -- vX.Y.Z` creates `changelog/releases/vX.Y.Z.json` from its template. Write it by `docs/release-notes.md`, for the people who use the intranet. Developer-only releases are for `staff`, summary "No changes you will notice", detail in `internal_notes`.
4. **Last commit.** `chore: release vX.Y.Z (Refs #<epic>)`, setting `"published": true`. Run `php artisan changelog:validate`. Push.
5. **Version PR.** `gh pr create --base main --title "<Milestone PR title from composer milestone:next>" --body-file "$env:TEMP\report.md"`. The body follows `docs/templates/report.md`, lists every issue shipped, and says `Refs #<epic>` (never `Closes`, so the Epic stays open until the deploy).
6. **Checks.** `gh pr checks <pr> --watch`. Fix failures on the version branch. **Stop and ask** if still red after one fix attempt.
7. **Check the names.** `composer milestone:check-release -- vX.Y.Z "<Release title from composer milestone:next>"`.
8. **Ship gate.** Summarise: milestone and issues, PR, checks, target `main`, merge method (merge commit), tag, deploy target (Cloudron, `intranet.apes.org.uk`), and that nothing auto-deploys. Then ask with AskQuestion using exactly:
   - a. Merge
   - b. Release
   - c. Deploy
   - d. Merge, release and deploy
   - e. Stop here

   Add `(Recommended)` to the best one (d when everything is green, e when something is blocked) and give the reason. Post the answer on the Epic. Run only what was chosen, in order. After each single step, ask again with only the remaining steps.
9. **Merge.** `gh pr merge <pr> --merge --delete-branch`. Confirm with `gh pr view <pr> --json state,mergeCommit`.

## Never

- Squash or rebase into `main`, or merge without the ship-gate answer in the current chat.
- Merge with an open issue in the milestone (the Epic excepted).
