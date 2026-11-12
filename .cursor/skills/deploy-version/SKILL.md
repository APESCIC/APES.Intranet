---
name: deploy-version
description: Deploy a released version of APES Intranet to Cloudron with evidence and close it out - run deploy.ps1 for the tag, check /healthz, check every issue is closed, post the release summary, close the Epic and the milestone, and merge main into dependencies. Use after release-version when the ship-gate answer includes deploy, or when the user says "deploy" or /deploy.
---

# Deploy a version and close it out

Follows `.cursor/rules/shipping.mdc` and `docs/deploy/cloudron.md`. Use the steps below for the summary and close-out, not `Close-IssueTree.ps1` or `Close-Milestone.ps1`. Needs the ship-gate answer (c or d) in the current chat. Every Cloudron app is production. Never print `.env` values, `APP_KEY` or addon credentials.

## Steps

1. **Check the release.** `gh release view vX.Y.Z`, and `ci` green on the tag's commit.
2. **Preflight** (read-only): `powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\preflight.ps1`. **Stop and ask** on any `FAIL` (for example the `cloudron` CLI isn't signed in).
3. **Deploy.** `powershell -NoProfile -ExecutionPolicy Bypass -File deploy\cloudron\deploy.ps1 -Tag vX.Y.Z`. It backs up, builds the tag, uploads, migrates, verifies and rolls back by itself on failure. Keep its summary.
   - Exit 1 means nothing changed or it rolled back. **Stop**: nothing closes, and reopen any issue that needs the fix. Ask how to proceed.
4. **Evidence.** `curl.exe -s https://intranet.apes.org.uk/healthz` must show `"version":"vX.Y.Z"` and `revision` = the first 12 characters of the tag's commit (`git rev-parse vX.Y.Z^{commit}`).
5. **Check the milestone.** `composer milestone:status -- vX.Y.Z` must pass (only the Epic may be open). If not, list the open issues and **stop**. Never close a milestone with open issues.
6. **Release summary.** Fill in `templates/release-summary.md` and post it on the Epic (`gh issue comment <epic> --body-file "$env:TEMP\release-summary.md"`).
7. **Close the Epic.** `gh issue close <epic> --reason completed`. Run `composer milestone:status -- vX.Y.Z` again: it must say `Epic: closed`.
8. **Close the milestone.** Add the summary to the milestone description, then close it:
   `gh api -X PATCH repos/APESCIC/APES.Intranet/milestones/<number> -f state=closed -f description=<summary>`. Look up its number with `gh api repos/APESCIC/APES.Intranet/milestones`; never write that number in a name or title.
9. **Dependencies.** `git switch dependencies`, `git pull --ff-only`, `git merge --no-edit origin/main`, `git push`. Then merge `origin/main` into any open version branch.
10. **Report** in the shape of `docs/templates/report.md`: milestone, PR and merge commit, issues, tag and release links, `/healthz` output and deploy summary.

## Never

- Deploy a branch, or a tag without a published release.
- Close anything after a failed deploy.
