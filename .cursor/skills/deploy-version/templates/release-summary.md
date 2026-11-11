# Release summary template

Post on the Epic after a verified deploy, and add it to the milestone description before closing the milestone.

```markdown
**Release summary**

- Version: `vX.Y.Z` (<milestone title>)
- Release: <GitHub release link>
- Merge: PR <link>, merge commit `<short sha>`
- Issues shipped: <#n list>
- Deploy evidence: `/healthz` shows version `vX.Y.Z` and revision `<12 characters>`; deploy summary: release `<id>`, backup `<id>`
- Anything you need to know or do: nothing / <what>
```
