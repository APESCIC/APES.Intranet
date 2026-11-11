---
name: plan-work
description: Plan any task or a whole version in APES Intranet before changing anything - investigate the code and issues, write a plan with a summary, decisions, numbered tasks with done-when checks, files, risks and a glossary, post it on the issue when asked, wait for approval, then name the agent. Use before any large or unclear task, for a whole milestone, or when the user says "plan", "make a plan" or /plan.
---

# Plan the work

Read only until Bambie approves. No edits, commits or comments other than posting the plan when asked.

## Steps

1. **Investigate.** Read the relevant code, docs and issues (`gh issue view <n>`, `gh issue list --milestone "<title>"`). Run `composer milestone:next` to see the next version.
2. **For a whole version:** list the milestone's issues in a sensible order, with what depends on what, and flag any too big for one PR (suggest how to split them).
3. **Write the plan** in plain English, short sentences, with these sections:
   1. Summary: what you found, five bullets or fewer.
   2. Decisions needed: a short table of options, with your recommendation.
   3. Tasks: numbered and in order. For each: what, why (one line), files, a "done when" check, and size (small, medium or large). Group them into PRs that are easy to review.
   4. Files: added, rewritten, deleted and kept.
   5. Risks, and how you'll avoid them.
   6. Glossary: every technical term in one plain sentence (reuse `docs/glossary.md`).
4. **Heading.** Use the agent name as the plan's heading: `<milestone title> – APES.Intranet`, or `APES.Intranet – <short task name>` for work with no milestone. Under 60 characters, with the version and never the milestone number.
5. **Post it** on the issue when asked: `gh issue comment <n> --body-file <plan>`, or as a checklist in the issue body.
6. **Ask for approval** with AskQuestion: "Approve the plan (Recommended)", "Revise it", "Stop here". Ask about each open decision the same way. **Wait.**
7. **After approval:** rename this chat to the plan's heading. If you can't, put `Suggested agent name: <name>` as the first line of your reply. Then carry out the tasks (for issues, with `work-on-issue`).
