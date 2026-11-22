# Moodle and intranet coexistence

**Milestone:** v0.6.13 (Task [#188](https://github.com/APESCIC/APES.Intranet/issues/188)).

While both systems run:

| Where | What you find |
| --- | --- |
| **My learning** (`/learning`) | New courses built in the intranet Learning Module |
| **My training** (`/hr/training`) | Compliance links to **Moodle Upskilling** (`upskilling.intranet.apes.org.uk`) and optional links to matching intranet courses |

The TutorLMS host (`lms.intranet.apes.org.uk`) has no courses. The HR LMS base
URL defaults to Moodle Upskilling (`HR_LMS_BASE_URL`). Nothing here changes
Moodle settings or content.

See also the [LMS inventory](../discovery/lms-inventory.md) and the
[pilot UAT script](pilot-uat.md).
