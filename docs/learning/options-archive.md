# Options for Moodle Upskilling and TutorLMS

**Milestone:** v0.6.13 (Task [#191](https://github.com/APESCIC/APES.Intranet/issues/191)).

Paper only. **No action** is taken on Moodle or TutorLMS here. Bambie decides
later, in a separate written approval that names the chosen option and the
milestone that will carry it out.

Context: [LMS inventory](../discovery/lms-inventory.md),
[readiness](readiness.md), [coexistence](coexistence.md).

## Systems in scope

| System | URL | Role today |
| --- | --- | --- |
| Moodle Upskilling | `upskilling.intranet.apes.org.uk` | Live courses (2 courses, small content set) |
| TutorLMS host | `lms.intranet.apes.org.uk` | Empty — no courses |
| Intranet Learning Module | `intranet.apes.org.uk` | New courses (pilot) |

## Options

### A. Keep both Moodle and TutorLMS (status quo)

Leave Moodle and TutorLMS running. Intranet Learning continues for new
courses. Coexistence signposting stays.

- **Pros:** No change risk; Moodle content stays where it is.
- **Cons:** Two empty-or-small LMS apps plus the intranet; ongoing Cloudron
  cost and sign-in surface.

### B. Keep Moodle; decommission TutorLMS only

TutorLMS has no courses. After confirming nothing else uses that host,
uninstall TutorLMS and point any remaining links at Moodle or the intranet.

- **Pros:** Removes an empty app; Moodle content unchanged.
- **Cons:** Still two training homes (Moodle + intranet) until a later replace.

### C. Archive Moodle content; keep Moodle offline or read-only

Export or freeze Moodle courses (for example `.mbz` backups and/or manual
copies of pages/questions into the intranet or a file store). Then stop
learner access to Moodle, or leave it read-only for a defined period.

- **Pros:** Content preserved; path toward a single home for new learning.
- **Cons:** Needs a content project; archive format and retention must be
  agreed; not authorised by this paper.

### D. Decommission Moodle after content is re-created or accepted as retired

Re-create needed Moodle courses in the intranet (or formally retire them),
run UAT, then uninstall Moodle. TutorLMS can go in the same or an earlier
step (option B).

- **Pros:** One learning home on the intranet.
- **Cons:** Highest effort; needs readiness criteria (content gap closed) and
  a dated cutover plan. **Not ready today** per [readiness.md](readiness.md).

### E. Keep Moodle indefinitely; use intranet only for new courses

Same as A for Moodle, with an explicit decision that Moodle remains the
compliance / legacy home and the intranet is only for new material.

- **Pros:** Clear product split; no migration.
- **Cons:** Dual systems forever unless revisited.

## Recommendation for the decision meeting

Prefer **B** soon (remove empty TutorLMS) and **E** or **A** for Moodle until
the content gap is closed. Only then revisit **C** or **D**. This paper does
not choose; Bambie must.

## Decision left open

| Decision | Owner | Status |
| --- | --- | --- |
| Keep / archive / decommission Moodle | Bambie | Deferred |
| Keep / decommission TutorLMS | Bambie | Deferred |
| Cutover date or milestone | Bambie | Deferred |

When decided, record the choice in [docs/decisions.md](../decisions.md) and
open a milestone that carries out only the approved option.
