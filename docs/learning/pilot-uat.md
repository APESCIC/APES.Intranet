# New-course pilot UAT

**Milestone:** v0.6.13 (Epic [#165](https://github.com/APESCIC/APES.Intranet/issues/165), Task [#187](https://github.com/APESCIC/APES.Intranet/issues/187)).

Use this script to prove a **new** intranet Learning course works for a small
group. Do **not** move Moodle courses or learners. Moodle at
`upskilling.intranet.apes.org.uk` stays live and untouched.

Run on the Herd preview (`https://apes-intranet.test`) with fake
`@example.test` accounts only. Never use real staff, volunteer or student data.

## Roles needed

| Role in the script | How to sign in locally |
| --- | --- |
| Author / learning manager | Developer sign-in → **Admin** (or Staff + Admin) |
| Learner | Developer sign-in → **Staff** |

## Preconditions

- [ ] Herd preview is up (`scripts\preview.ps1`); Vite on port 5174
- [ ] Learning module is enabled (default)
- [ ] You are not changing Moodle or TutorLMS settings or content

## Script

### A. Author a pilot course

1. Sign in as Admin.
2. Open **Learning → Courses** (`/learning/courses`).
3. Create a course titled **Pilot: Intranet learning (v0.6.13)** with a short summary.
4. Add one section (for example **Welcome**) and one lesson with a short published body.
5. Publish the lesson so the course becomes published.
6. Open enrolments for the course and manually enrol the Staff demo user (or add a group rule for Staff and sync).

### B. Learner path

1. Sign out, then sign in as Staff.
2. Open **My learning** (`/learning`).
3. Confirm the pilot course appears and open it.
4. Complete the lesson.
5. Confirm progress updates (lesson complete / course progress).

### C. Coexistence (smoke only)

1. Still as Staff, open **My training** (`/hr/training`).
2. Confirm LMS links still appear (external Moodle / configured LMS base).
3. Confirm you can tell intranet courses apart from LMS links (full signposting is #188).

### D. Author checks after learner activity

1. Sign in as Admin again.
2. Open the pilot course’s enrolments and confirm the Staff learner is enrolled.
3. If reports are available for your role, open the course report and confirm the learner appears.

## Pass / fail

| Check | Pass when |
| --- | --- |
| Author can create and publish a new course | Lesson publishes; course status published |
| Learner can enrol and complete | Progress shows complete for the lesson |
| Moodle untouched | No Moodle admin or content change was made |
| Fake data only | Only `@example.test` accounts used |

## Record the result

Post on Epic #165:

- Date and who ran the script
- Pass or fail for each check above
- Issues found (bugs, confusing copy, missing permissions)
- Owner sign-off line (Bambie or delegate)

## Out of scope

- Importing Moodle courses or question banks
- Changing Moodle or TutorLMS
- Production Cloudron UAT (optional later; Herd is enough for this milestone)
