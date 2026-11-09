# Contributing

APES Intranet is maintained by APES CIC. Laragon is the only supported local
set-up. Set-up steps are in
[docs/local-development.md](docs/local-development.md). Check every change in
the Laragon preview at <http://apes-intranet.test>, with the demo data loaded,
before it ships.

## How work ships

Work ships once per milestone, in four stages, in this order:

1. Every issue in the milestone is finished and committed on the milestone
   branch, `milestone/<milestone-slug>`, behind one draft pull request into
   `main`. The pull request lists `Closes #<n>` for each issue.
2. All the milestone's work goes to `main` in one merge: a merge commit, not a
   squash or a rebase.
3. Release, tagged with the milestone's version.
4. Deploy that release.

Nothing reaches `main`, and nothing is tagged, released or deployed, before
stage 2. Bambie approves the merge, release and deploy through the ship-gate
question. Issues stay open when they are committed and close when the
milestone pull request merges. The Epic and the milestone close after a
verified deploy.

A hotfix may ship earlier only with Bambie's approval. It goes on its own
`cursor/fix/<issue>-<slug>` branch, is merged with a merge commit and released
as a patch, and `main` is then merged back into the milestone branch. `main`
accepts merge commits only.

Issue titles start with the type: `[Epic]`, `[Story]`, `[Task]`, `[Bug]`,
`[Chore]` or `[Spike]`.

The full process, including the definition of done, is in the "Milestone
shipping" section of [AGENTS.md](AGENTS.md#milestone-shipping).
