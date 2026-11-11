---
name: release-version
description: Release a merged version of APES Intranet - one annotated tag vX.Y.Z on the merge commit in main and one GitHub release with notes in the report template. Use after finish-version has merged the version PR and the ship-gate answer includes release, or when the user says "release" or /release.
---

# Release a version

Follows `.cursor/rules/shipping.mdc`. Needs the ship-gate answer (b or d) in the current chat.

## Steps

1. **Find the merge commit.** `gh pr view <version pr> --json mergeCommit` and `git fetch origin --tags`.
2. **Wait for `ci` on `main`.** `gh run list --branch main --limit 3`. **Stop and ask** if `ci` failed on the merge commit.
3. **Check the names.** `composer milestone:check-release -- vX.Y.Z "<release title>"`.
4. **Tag.** `git tag -a vX.Y.Z -m "vX.Y.Z" <merge sha>`, then `git push origin vX.Y.Z`. CI runs again on the tag and fails if the release note isn't published.
5. **Release.** Write notes in the shape of `docs/templates/report.md` (What changed, Why, How it was checked, What you need to do, Explained), then
   `gh release create vX.Y.Z --title "vX.Y.Z <milestone name>" --notes-file <notes> --verify-tag`.
6. **Confirm.** `gh release view vX.Y.Z` and `gh run list --branch vX.Y.Z --limit 1` (CI on the tag must go green before the deploy).
7. If the ship-gate answer was b, ask the ship gate again with the remaining step (deploy).

## Never

- Tag anything but the merge commit, or move or rewrite an existing tag or release.
