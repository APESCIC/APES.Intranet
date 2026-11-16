# DPIA: Learning records

| Item | Value |
| --- | --- |
| Status | **Draft — awaiting owner approval** (do not store real learner personal data until approved) |
| Controller | Association of Protecting Exotic Species CIC (APES CIC) |
| Owner and approver | Bambie Murphy, Director |
| Feature | Intranet Learning Module: courses, enrolment, progress and completion; future assessment submissions and grades (v0.6.6 core, Epic #162, issue #170; grades UI from v0.6.7) |
| Template | ICO sample DPIA template, steps 1 to 7 |
| Drafted | 16 November 2026 |
| Review | Before real (non-demo) learner data is stored in production, after any change to categories of data or access, and once a year while Learning features are live |

This assessment is kept in the repository so it can be reviewed with the code
([where documents live](../decisions.md#where-documents-live)). It is **not** filed in SharePoint.
Internal hosting detail that must stay private can sit in
`APESCIC/.github-private` under `docs/apes-intranet/`; this public copy leaves
out Cloudron app IDs and similar inventory.

A short pointer also lives at [docs/learning/dpia.md](../learning/dpia.md) so the
issue’s older path still resolves.

Retention periods that the app will enforce (when purge jobs land) are in
`config/intranet.php` under `retention.learning`.

HR “My training” (compliance links to the external LMS) is a separate feature
and is covered by the [HR records DPIA](hr-records.md). This assessment covers
the new Learning Module only. Moodle at upskilling.intranet.apes.org.uk stays
live and untouched; nothing here migrates or decommissions it.

## Step 1: Why a DPIA is needed

APES CIC will keep learning information about staff, volunteers and students in
the intranet so people can take courses, and so instructors and learning admins
can enrol cohorts, track progress and (from a later version) mark assessments.
That includes progress through lessons, completion status, and later
submissions and grades. Some learners may be minors (under 18).

The ICO lists processing that involves vulnerable people (including children)
and systematic evaluation of people among kinds of processing that are likely
to need a DPIA. APES CIC has therefore decided that a DPIA is required before
Learning features hold real learner records
([decisions](../decisions.md#features)).

## Step 2: The processing

### Nature

1. An authorised person signs in through Cloudron OIDC. Access to Learning
   screens depends on the Learning Module being enabled and on scoped learning
   roles or permissions (learner, instructor, assessor, grade approver,
   learning admin), not on Admin or HR alone.
2. Authors create courses, sections and lessons (rich text sanitised). Publishing
   creates a stable version; learners who have already started keep their
   version.
3. Learners are enrolled manually, by canonical Cloudron group sync, or by
   optional self-enrol, with start/end dates and prerequisites.
4. The app stores enrolment, lesson/course progress and completion in the
   Cloudron app’s MySQL database on the London server only. Grades and
   submissions are in scope for this DPIA’s risks and retention, but the
   gradebook UI ships in a later version; permissions that deny grade
   edit/release without a learning assignment still land in v0.6.6.
5. “My learning” and the course player show the learner their own progress.
   Service workers must not cache grade data.

### Scope

| Area | What is kept | What is not kept |
| --- | --- | --- |
| Course content | Titles, structure, sanitised lesson HTML, published versions | Unrelated HR fields; Moodle course import |
| Enrolment | Who is on which course/cohort, method, dates, prerequisite outcomes | Automatic deletion of the Cloudron account |
| Progress / completion | Lesson and course completion state and timestamps | Continuous location or device tracking |
| Submissions / grades (later) | Assessment answers and grade values once that version ships | Public gradeboards without need |
| Learning role assignments | Role, optional course/cohort scope, audit of changes | Implicit Admin/HR grade power |

- **People:** APES CIC staff, volunteers and students with intranet accounts who
  are enrolled or assigned a learning role. Some may be under 18.
- **Special category:** Learning records are not special category by default.
  Do not put health, DBS or other special category data into lesson content or
  free-text submissions without a fresh decision and DPIA update.
- **Volume:** one enrolment and progress trail per learner per course; modest
  numbers of courses for a small organisation.
- **Area:** processing for APES CIC’s UK operations and training.

### Context

- Learners expect progress to be private to them and to people with a scoped
  learning role for that course or cohort.
- Fake demo data only in local and CI. Real learner data must not enter the
  public repository or agent chats.
- Minors (students/volunteers under 18): same access and audit rules; no extra
  categories of data are collected for them beyond what the course needs.
  Parent or guardian contact is **not** stored in the Learning Module in this
  version.
- HR training compliance links remain separate from “My learning”.

### Purposes

- Deliver and record intranet courses for staff, volunteers and students.
- Track enrolment, progress and completion for operational and safeguarding
  competence where courses support that purpose.
- Support later assessment and gradebook features under the same retention and
  access rules.
- Benefit to learners: clear progress, stable lesson versions, mobile-friendly
  player.

### Hosting and security evidence (16 November 2026)

Taken from the issue hosting evidence (same hosting as HR records):

- Official Learning application data lives on the Cloudron server in **London**
  only, not in SharePoint as the system of record for these fields.
- Disk encryption of this Linode was confirmed by Bambie on 12 November 2026
  (Europe/London).
- Cloudron backups stay in the current Paris bucket, kept for 30 days, with
  Cloudron AES-256 backup encryption and file-name encryption, plus a restore
  test.
- Hardening from the 12 November 2026 IT Admin review is a **prerequisite
  before real learner documents or submissions are relied on** beside the app,
  and runs alongside the build.
- Do **not** claim SOC 2 Type II, SOC 1, SOC 3, CSA STAR or HDS for this
  hosting. Cyber Essentials covers Akamai’s UK offices and staff, not this
  server.

## Step 3: Consultation

- **Owner / Director:** must approve this DPIA before real learner data is
  stored. Build of the Learning Module may proceed against fake demo data only
  until then.
- **Learning roles:** instructors, assessors, grade approvers and learning
  admins will use scoped assignments; the permission matrix is tested in code.
- **Learners:** informed through privacy notice updates when real data goes
  live; local demo uses fake accounts only.
- **Data protection advice:** APES CIC does not have a statutory DPO. The
  Director decides, and may ask for outside advice before real data is loaded.

## Step 4: Necessity and proportionality

- **Lawful bases (summary):**
  - Legitimate interests and/or contract / steps prior to contract for staff
    and volunteer training needed to do the role (UK GDPR Article 6).
  - Legitimate interests / public task style interests for student training
    that APES CIC delivers as part of its programmes, limited to what the
    course needs.
  - Grades and formal assessment outcomes (when shipped) rely on the same
    bases; no special category processing is intended for ordinary course
    grades.
- **Minimisation:** scoped learning roles; no grade edit/release without a
  learning assignment (including Admin); TipTap output sanitised; fake data in
  development; no continuous tracking; service worker must not cache grades.
- **Retention:** periods in `config/intranet.php` `retention.learning` (see
  Step 7). Purge jobs may follow later; the periods are proposed now for owner
  agreement.
- **Processors and transfers:** Cloudron hosting and backups as above. No new
  third-party LMS SaaS in v0.6.6. Moodle remains a separate system.

## Step 5: Risks

| Risk to people | Likelihood | Severity | Overall |
| --- | --- | --- | --- |
| Unauthorised staff see another learner’s progress or grades | Possible | Significant | Medium |
| Under-18 learner data exposed more widely than needed | Possible | Severe | High |
| Admin/HR assume they can edit or release grades without a learning role | Possible | Significant | Medium |
| Excessive retention of enrolments, progress or grades | Possible | Significant | Medium |
| Grade or progress data cached in a service worker or public channel | Possible | Significant | Medium |
| Real learner data used in demos or CI | Remote | Severe | Medium |
| Special category data pasted into lessons or submissions | Possible | Severe | High |

## Step 6: Measures to reduce risk

| Risk | Measure | Effect | Residual risk | Approved |
| --- | --- | --- | --- | --- |
| Unauthorised access | Module flag, scoped learning roles/policies, matrix tests | Reduced | Low | Pending owner |
| Under-18 exposure | Same minimisation; no extra fields; access limited to need | Reduced | Low | Pending owner |
| Implicit Admin/HR grade power | Deny grade edit/release without learning assignment; no gradebook UI in v0.6.6 | Reduced | Low | Pending owner |
| Excessive retention | Periods in config; yearly review | Reduced | Low | Pending owner |
| Cache / channel leakage | No grade endpoints in SW caches; repo/agent fake-data rules | Reduced | Low | Pending owner |
| Demo/CI exposure | Demo seeders and tests use `@example.test` only | Reduced | Low | Pending owner |
| Special category in content | Authoring guidance; sanitiser; escalate if a course needs it | Reduced | Low | Pending owner |

## Step 7: Sign-off and outcomes

| Item | Name or date | Notes |
| --- | --- | --- |
| Measures approved by | *Awaiting Bambie Murphy, Director* | Do not invent approval |
| Residual risks approved by | *Awaiting owner* | Residual risks intended to be low after measures; no ICO consultation proposed for this scoped build once approved |
| Decision | **Draft only** | Retention periods below are proposed; owner must agree before real learner PII is stored |
| Approval date | — | Record on this file, on issue #170 and on Epic #162 when the owner approves |
| Filed | This repository path only | Not SharePoint |
| Review date | Within one year of approval | Or sooner before loading real production learner data, or on scope change |

### Proposed retention (config keys)

Values are years after the learner leaves the organisation (or after the
enrolment ends, where noted), overridable by environment variables. Safe
minimums apply if unset. **Owner agreement required.**

| Record | Default | Env override |
| --- | --- | --- |
| Enrolment rows | 6 years after enrolment end or leave | `LEARNING_RETENTION_ENROLMENT_YEARS_AFTER_END` |
| Lesson / course progress and completion | 6 years after enrolment end or leave | `LEARNING_RETENTION_PROGRESS_YEARS_AFTER_END` |
| Assessment submissions (when shipped) | 6 years after enrolment end or leave | `LEARNING_RETENTION_SUBMISSION_YEARS_AFTER_END` |
| Grades (when shipped) | 6 years after enrolment end or leave | `LEARNING_RETENTION_GRADE_YEARS_AFTER_END` |
| Learning role assignments | 2 years after the assignment ends | `LEARNING_RETENTION_ASSIGNMENT_YEARS_AFTER_END` |
| Course content versions (published) | Keep while the course exists; archive copies 6 years after course archival | `LEARNING_RETENTION_COURSE_VERSION_YEARS_AFTER_ARCHIVE` |

Audit log retention stays under `retention.audit_log_days` (existing setting).
