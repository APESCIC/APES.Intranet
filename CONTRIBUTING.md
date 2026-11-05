# Contributing

APES Intranet is maintained by APES CIC. Set-up steps are in
[docs/local-development.md](docs/local-development.md). Check every change in
the Laragon preview before it ships.

## How work ships

Work ships once per milestone, in four stages, in this order:

1. Every issue in the milestone is finished and committed on the milestone
   branch, `milestone/<milestone-slug>`.
2. All the milestone's work goes to `main` in one merge: a merge commit, not a
   squash or a rebase.
3. Release.
4. Deploy.

Nothing reaches `main`, and nothing is tagged, released or deployed, before
stage 2. A hotfix may ship earlier only with Bambie's written approval on its
issue.

The full process, including the definition of done, is in the "Milestone
shipping" section of [AGENTS.md](AGENTS.md#milestone-shipping).
