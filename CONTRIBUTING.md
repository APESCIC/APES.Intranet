# Contributing

APES Intranet is maintained by APES CIC. Laragon is the only supported local
set-up. Set-up steps are in
[docs/local-development.md](docs/local-development.md). Before any work, run
`scripts\preview.ps1` (it loads the latest code and checks the Laragon
preview), then check every change at <http://apes-intranet.test> with the demo
data loaded.

## How work ships

Every milestone is named by its version and ships once: all its issues are
committed on one `milestone/vX-Y-Z-<slug>` branch behind one draft pull
request into `main`, which is merged once with a merge commit, tagged and
released as `vX.Y.Z`, and deployed. Issues close when that pull request
merges. A hotfix ships earlier only with Bambie's approval.

The rule, with the names, the flow and the hotfix route, is
[`.cursor/rules/version-shipping.mdc`](.cursor/rules/version-shipping.mdc).
The decisions behind it are in [AGENTS.md](AGENTS.md#shipping-and-versioning).

- `composer milestone:next` prints the next version, its branch and its pull
  request and release titles.
- `composer milestone:check-title -- "<title>"` and
  `composer milestone:check-branch -- "<branch>"` tell you what is wrong. The
  `milestone-version-check` CI job runs them on every pull request.
- To check your branch name before each push, run
  `git config core.hooksPath .githooks` once.

Issue titles start with the type: `[Epic]`, `[Story]`, `[Task]`, `[Bug]`,
`[Chore]` or `[Spike]`. Commits use Conventional Commits with `Refs #<n>`.
