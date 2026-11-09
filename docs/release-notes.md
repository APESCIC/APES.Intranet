# Release notes

Every release of APES Intranet has one reviewed release note. Signed-in
people read them in the change log hub at `/change-log`, and the footer
version pill links to the note for the version that is deployed.

## Where they live

- One file per release: `changelog/releases/vX.Y.Z.json`, named exactly after
  the release tag. Use a full version (`vMAJOR.MINOR.PATCH`), never a
  wildcard such as `v0.4.x`.
- Start from `changelog/releases/_template.json`. Files that start with `_`
  are templates and are never synced.
- The files are the source of truth. There is no browser editor. Every deploy
  runs `php artisan changelog:sync`, which copies them into the `releases`
  table. The sync is idempotent: it creates new versions, updates changed
  ones and never deletes a row. A row whose file has gone is kept and
  reported.

## Fields

| Field | What to write |
| --- | --- |
| `version` | The release tag, such as `v0.3.11`. Must match the file name. |
| `released_at` | The release date, `YYYY-MM-DD`. |
| `title` | A short name for the release, up to 120 characters. |
| `summary` | One or two plain sentences on what the release means for the people who use the intranet, up to 500 characters. |
| `audiences` | Who the note is for: one or more of `staff`, `volunteers` and `students`. Only those groups see it. |
| `changes` | Lists of plain sentences under `added`, `changed`, `fixed`, `removed`, `security` or `accessibility`. Leave out any type with nothing in it. |
| `internal_notes` | Optional. Shown to admins only, for context the rest of APES does not need. |
| `published` | `false` while the note is a draft, `true` once it has been reviewed. Drafts are synced but only admins see them. |

## Editorial rules

Write for the people who use the intranet, in UK English and plain language.
Say what they can now do or what works differently, not how it was built.

Never put any of these in a release note, including in `internal_notes`:

- passwords, tokens, keys, `.env` values or any other secret;
- names, email addresses, phone numbers or anything else about a real person;
- learner, HR, health, DBS, volunteer or agreement records;
- hostnames, IP addresses, server paths or SQL;
- commit hashes or raw GitHub release notes.

`php artisan changelog:validate` checks the schema and looks for text that
resembles these. It runs in CI on every push. It cannot catch everything, so
the pull request review is the real check.

Developer-only releases are tagged for `staff` only, with a short summary such
as "No changes you will notice", and put the detail in `internal_notes`.

## Publishing a release note

The note is part of the milestone's work, so it ships in the milestone pull
request:

1. Add `changelog/releases/vX.Y.Z.json` on the milestone branch with
   `"published": false`, and keep it up to date as issues are committed.
2. As the last commit before the ship gate (`chore: release vX.Y.Z`), review
   the note and set `"published": true`.
3. Tag and release as usual. CI runs again on the `vX.Y.Z` tag and fails if
   the tag has no published note
   (`php artisan changelog:validate --require-published=vX.Y.Z`).
   `deploy/cloudron/deploy.ps1` also refuses to deploy a tag without one.
4. After the deploy, check that the footer pill shows the new version and
   links to its note.

## Unpublishing or correcting a note

- To fix wording, edit the file on the next milestone branch (or a hotfix
  branch). The next deploy's sync updates the row.
- To hide a note, set `"published": false` and deploy. The row stays, but only
  admins can see it.
- Never delete a released note's file to hide it: the sync keeps the row and
  reports it as orphaned.

## Rolling back

- A release rolled back by `deploy.ps1` keeps the hub's rows from the newer
  release, because the sync never deletes. The footer pill and "Current
  version" badge follow the deployed `VERSION` file, so they show the
  rolled-back version straight away.
- If a published note is wrong, unpublish it as above, rather than reverting
  the file.
- If `changelog:sync` fails during a deploy, the deploy carries on and the hub
  keeps its previous entries. The error is logged in `storage/logs`. Fix the
  file and redeploy, or run `php artisan changelog:sync` on the app.
