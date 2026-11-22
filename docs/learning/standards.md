# Learning content standards decision

**Decided by Bambie** for v0.6.12 (Epic
[#164](https://github.com/APESCIC/APES.Intranet/issues/164), Spike
[#186](https://github.com/APESCIC/APES.Intranet/issues/186)).

Evidence: the [LMS inventory](../discovery/lms-inventory.md) (8 November 2026).
That discovery checked both Cloudron LMS apps (TutorLMS host and Moodle
“Upskilling”). Full private detail lives in
`APESCIC/.github-private` `docs/apes-intranet/discovery/lms-inventory.md`.

## Decision

The intranet Learning Module does **not** implement or require these learning
content standards:

| Standard | Decision |
| --- | --- |
| SCORM 1.2 / 2004 | Not used — do not build support |
| H5P | Not used — do not build support |
| xAPI / cmi5 | Not used — do not build support |
| LTI (consumer or publisher) | Not used — do not build support |
| IMS content packages | Not used — do not build support |
| Open Badges | Not used — do not build support |

Neither LMS app uses those standards in practice (no plugins or zero
activities). The Learning Module ships native courses, lessons, quizzes and
assignments in Blade. Packages such as `php-elearning-toolkit` stay off the
stack until a standard is approved.

## Trigger for adding a standard

Any standard in the table above (or another e-learning interchange format)
needs **Bambie's separate written approval** before:

- installing a package or plugin for that standard;
- designing import/export or player support;
- filing a milestone that builds it.

Approval should name the standard, why it is needed, and which milestone owns
the work. Until then, treat “not used” as the lasting decision, not a temporary
gap.

## Out of scope here

- Building any standard.
- Migrating, archiving or decommissioning Moodle or TutorLMS.
- Changing HR “My training” LMS links (#53) beyond the separate v0.6.12 bridge
  that reads native Learning completions alongside those links.
