---
name: status
description: Command - show where the current version is up to, read only - next version, open issues, PRs and checks.
disable-model-invocation: true
---

# /status

Read only. Change nothing.

1. `composer milestone:next`: the next version, its branch and titles.
2. `composer milestone:status -- <version>`: its open issues and the Epic.
3. `gh pr list --base <version branch>` and `gh pr list --base main`: open PRs, with `gh pr checks <pr>` for each.
4. `git status -sb`: the current branch and any uncommitted changes.
5. Report in plain words, in the shape of `docs/templates/report.md`: where the version is, what's next, and anything blocked. Offer the next step with AskQuestion.
