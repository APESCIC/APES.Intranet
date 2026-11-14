# Report template

Use this shape at the end of every task, in every PR description and in every
GitHub Release. Write plain words. Link logs instead of pasting them, unless
someone asks for the raw output.

```markdown
## What changed

Plain-English summary of what is different now.

## Why

One or two sentences on the reason, with the issue link.

## How it was checked

- `<command>`: result (for example "312 passed")
- Manual check in the Herd preview: what you looked at

## What you need to do

Anything the reader must do, or "Nothing".

## Explained

- **<term or decision>:** one plain sentence.
```

Release notes for the intranet's change log are a separate format, kept in
`changelog/releases/` (see [release-notes.md](../release-notes.md)).
