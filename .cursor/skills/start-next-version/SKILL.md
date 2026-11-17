---
name: start-next-version
description: Start or resume the next version of APES Intranet - live preview gate, find the next milestone, create or update its version branch, order the issues, then work them one at a time with work-on-issue. Use when the user says "complete the next version", "next version", "start the milestone", "resume the milestone" or /next-version.
---

# Start the next version

Follows `.cursor/rules/shipping.mdc`. One Cursor plan per version: when a new chat resumes a version, carry on with its existing plan (the approved copy is a comment on the Epic).

## Steps

1. **Live preview gate.** Follow `.cursor/skills/live-preview-gate/SKILL.md`. Stop if it fails twice.
2. **Find the version.** Run `composer milestone:next` and show its output.
   - **Stop and ask** if it fails, prints a warning about the milestone, or names a different milestone than the user asked for.
3. **Version branch.** Use the printed "Milestone branch".
   - Missing: `git switch -c <branch> origin/main`, then `git push -u origin HEAD`.
   - Exists: `git switch <branch>`, then `git merge --no-edit origin/main`.
   - Then `git merge --no-edit origin/dependencies` and push.
   - **Stop and ask** on any merge conflict. Call `SetActiveBranch`, then run the preview gate again.
4. **List and order the issues.** `gh issue list --milestone "<milestone title>" --state open`. Put them in a sensible order (dependencies first) and flag any that are too big to do in one PR. If there's no confirmed plan yet, run the `plan-work` skill for the whole version and wait for approval.
5. **Work each issue** with the `work-on-issue` skill, one at a time, in the planned order. Don't ask between issues. Stop only for a failure or blocker the plan doesn't settle.
6. **Hand over** to `finish-version` when `composer milestone:status -- vX.Y.Z` shows no open issues.

## Never

- Use GitHub's milestone number in a name.
- Rename the Project coordinator chat (title stays `Next version plan – APES.Intranet` unless Bambie asks).
- Start an issue before the previous one is closed (or moved with Bambie's approval).
- Bump the version for one issue.
