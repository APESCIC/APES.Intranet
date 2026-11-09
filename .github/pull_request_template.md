# Pull request

One pull request per milestone, from `milestone/vX-Y-Z-<slug>` into `main`,
titled `vX.Y.Z: <milestone name>` (`composer milestone:next` prints both).
Open it as a draft after the first commit.

## Summary

Say what the milestone delivers and why.

- Milestone:
- Epic: #
- Ships as: `vX.Y.Z`

## Issues

List every issue in the milestone, including the Epic.

- [ ] Closes #

## Checklist

- [ ] Checked in the Laragon preview at http://apes-intranet.test, with the demo data
- [ ] No secrets or real personal data
- [ ] Tests pass and `ci` is green
- [ ] Every issue is committed on the milestone branch
- [ ] Ship-gate answer recorded on the Epic before merge (merge commit only)
