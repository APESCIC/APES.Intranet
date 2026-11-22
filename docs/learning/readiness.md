# Replacement readiness criteria and gap report

**Milestone:** v0.6.13 (Task [#190](https://github.com/APESCIC/APES.Intranet/issues/190), Epic [#165](https://github.com/APESCIC/APES.Intranet/issues/165)).

What must be true before Moodle Upskilling could be *considered* for
replacement. This is criteria and a gap report only. It does **not** authorise
migration, cutover, archive or decommission. Moodle stays live and untouched
until Bambie separately approves a later decision (see
[options-archive.md](options-archive.md)).

Evidence: [LMS inventory](../discovery/lms-inventory.md) (8 Nov 2026),
[pilot UAT](pilot-uat.md), [coexistence](coexistence.md),
[learning restore drill](../deploy/learning-restore.md), and Learning Module
releases through v0.6.12.

## Criteria (agreed for this report)

Before a replace decision, all of the following should be true:

1. **New-course capability** — Staff can author, enrol, complete and (where
   needed) certificate a new course in the intranet Learning Module.
2. **Pilot UAT** — A small-group UAT script has been run and signed off with
   no blocking issues (or issues have an agreed owner and date).
3. **Coexistence** — Learners can find Moodle courses and intranet courses
   without confusion; Moodle links still work.
4. **Backup and restore** — Intranet learning data can be restored from a
   Cloudron backup onto a throwaway app; rollback steps are written.
5. **Content standards** — No SCORM/H5P/xAPI/LTI requirement blocks the
   intranet path ([standards.md](standards.md)).
6. **Content gap** — Moodle’s existing courses/pages/quizzes are either
   re-created in the intranet, intentionally left on Moodle, or covered by an
   approved archive plan (not done in this milestone).
7. **Access and DPIA** — Learning DPIA and HR training links remain accurate;
   Cloudron sign-in continues for both systems while they coexist.
8. **Owner decision** — Bambie has a written, dated decision to keep, archive
   or decommission Moodle/TutorLMS (separate from this report).

## Gap report (against the pilot, 22 Nov 2026)

| Criterion | Status | Notes |
| --- | --- | --- |
| 1. New-course capability | Met | Learning Module shipped (courses, enrolment, progress, assessment, certificates, reports) |
| 2. Pilot UAT | Met | [pilot-uat.md](pilot-uat.md) run on Herd; sign-off on Epic #165 |
| 3. Coexistence | Met | [coexistence.md](coexistence.md); HR LMS default → Moodle Upskilling |
| 4. Backup and restore | Met | [learning-restore.md](../deploy/learning-restore.md) drill on throwaway clone |
| 5. Content standards | Met | Standards not used on Moodle/TutorLMS; intranet does not require them |
| 6. Content gap | Open | Moodle still holds 2 courses, 19 pages, 2 quizzes, 32 MCQs (inventory). Not migrated (out of scope) |
| 7. Access and DPIA | Met for coexistence | DPIA covers Learning Module; Moodle remains a separate system |
| 8. Owner decision | Open | Options paper (#191) only; no keep/archive/decommission decision yet |

## Conclusion

The intranet is ready to run **new** courses beside Moodle. It is **not** ready
to replace Moodle until the content gap is closed (or explicitly accepted) and
Bambie decides the fate of Moodle and TutorLMS. No migration or cutover is
authorised by this document.
