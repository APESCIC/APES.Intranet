# LMS content and standards inventory

Discovery for [#155](https://github.com/APESCIC/APES.Intranet/issues/155),
part of milestone v0.3.5 Platform discovery (Epic
[#154](https://github.com/APESCIC/APES.Intranet/issues/154)). Checked on
8 November 2026.

This inventory does **not** authorise any build work. A Training Module, a
cutover or a decommission each needs Bambie's separate approval of a build
milestone.

This repository is public, so this copy leaves out app IDs, software and
plugin versions and account details. The full inventory belongs in
SharePoint, filed by Bambie (#250).

## How it was checked

- Read-only `cloudron exec` commands on the two LMS apps: plugin and theme
  lists, and aggregate counts from the databases. Each script was pushed to
  `/tmp`, run once and deleted.
- Moodle was read through its own API, so nothing was written apart from
  Moodle's normal cache.
- No names, email addresses, grades, enrolment rows or configuration secrets
  were read into this document, the issue or the chat. Counts only.
- Packagist metadata and the Packagist security advisories API for package
  candidates. No package was installed.

## Summary

- APES has **two** LMS apps on Cloudron, not one. The separate Moodle is
  **confirmed**.
- The TutorLMS host that the README links to has **no courses**. The only
  course content is on the Moodle ("Upskilling").
- Both are small: Moodle has 2 courses, 19 pages, 2 quizzes and 32
  multiple-choice questions, with 2 enrolments and no completions.
- **None** of SCORM, H5P, xAPI or LTI is in use on either app.
- Both apps sign in through Cloudron (OpenID Connect or OAuth 2).

## App 1: TutorLMS on WordPress

<https://lms.intranet.apes.org.uk/>: the free edition of TutorLMS on
WordPress, signing in through Cloudron. There is no TutorLMS Pro, and no
SCORM, H5P, xAPI or LTI plugin.

| Item | Count |
| --- | --- |
| Courses, lessons, topics, quizzes, assignments | 0 (no Tutor course post types exist) |
| Quiz questions and attempts | 0 |
| Orders, carts, coupons, earnings, withdrawals | 0 (monetisation unused) |
| Pages | 5 published, 2 in the bin |
| Posts | 2, both in the bin |
| Media library items | 0 |

## App 2: Moodle ("Upskilling")

Moodle 5, signing in through Cloudron with OAuth 2 only. Completion tracking
and competencies are on (with no competency frameworks), and there are no
badges.

| Item | Count |
| --- | --- |
| Courses | 2, in 4 categories |
| Page activities | 19 |
| Forums | 2 |
| Quizzes | 2 |
| Questions | 32, all multiple choice |
| Every other activity type (assignment, book, lesson, H5P, SCORM, LTI, IMS content package, workshop, wiki and so on) | 0 |
| Enrolments | 2 |
| Course completions | 0 |

## Learning standards

Marked from the evidence above only. Nothing here is a requirement: Bambie
approves the minimum standards later.

| Standard | TutorLMS host | Moodle | Status |
| --- | --- | --- | --- |
| SCORM 1.2 / 2004 | No plugin | 0 SCORM packages | **Not used** |
| H5P | No plugin | 0 H5P activities and 0 deployed H5P items (only Moodle's bundled core libraries) | **Not used** |
| xAPI / cmi5 | No plugin | 0 xAPI states | **Not used** |
| LTI (as a consumer of external tools) | No plugin | 0 tool types, 0 LTI activities | **Not used** |
| LTI (publishing courses to others) | No plugin | 0 published tools | **Not used** |
| IMS content packages | No plugin | 0 | **Not used** |
| Open Badges | No plugin | 0 badges | **Not used** |
| Certificates | Certificate customiser active, but there are no courses to certify | No certificate plugin | **Not used** in practice |
| Quiz formats beyond multiple choice | None | Only multiple choice | **Not used** |

## Export and import reality

- Moodle course backups (`.mbz`) only restore into Moodle. There is no claim
  that a future Laravel module could import them.
- With 19 pages and 32 questions, re-creating the content by hand, or
  exporting the questions in Moodle XML or GIFT, is realistic. A migration
  tool would not be needed.
- TutorLMS has no courses, so there is nothing to export from it.
- Not checked: the export tools in either admin UI. Treat these as unknown
  until someone with admin access looks.

## Laravel package candidates

From Packagist on 8 November 2026. None of these packages had Packagist
security advisories. No code was reviewed or installed.

| Package | Latest | Licence | PHP and Laravel | Fit for this repo |
| --- | --- | --- | --- | --- |
| `tapp/filament-lms` | v5.2.7 (7 Nov 2026) | MIT | PHP ^8.3; `illuminate/contracts` ^12 or ^13: **pass** | **Fail.** It needs the Filament 5 admin panel. The intranet uses its own Blade and Tailwind shell, with the same look as MyAPES Account. |
| `escolalms/courses` | 0.4.45 (Aug 2025) | MIT | PHP >=7.4; `laravel/framework` >=8 (no upper bound): **unknown** | **Unknown.** It is part of the EscolaLMS headless suite, with its own API and auth assumptions. Pre-1.0, and not tested against Laravel 13. |
| `escolalms/api` | 1.0.55 (Oct 2026) | MIT | `laravel/framework` ^9: **fail** | **Fail.** Laravel 9 only. |
| `lms-laravel/course` | dev-master only (2016) | MIT | No release: **fail** | **Fail.** Abandoned. |
| `cms-orbit/lms` | 4.6.1 (Sep 2026) | MIT | PHP ^8.4, Laravel ^13, `cms-orbit/core` ^4.6 | **Rejected.** Coupled to the Orbit CMS, and its PHP ^8.4 floor is above the repo's PHP 8.3 floor. |
| `daniel-jorg-schuppelius/php-elearning-toolkit` | v0.1.3 (Sep 2026) | Apache-2.0 | PHP >=8.2 <8.6, framework-free: **pass** | **Not needed now.** Pre-1.0 SCORM and xAPI helpers. Only worth a look if a standard is approved later. |

## Notes for later decisions

These are observations, not decisions:

1. The README and #53 point people to the TutorLMS host, which has no
   courses. The real content is on the Moodle. #53 is unchanged by this
   Spike; a coordination comment links it here.
2. No standard is in use, so the evidence does not require SCORM, H5P, xAPI
   or LTI for a Training Module MVP. Bambie approves the minimum standards.
3. No candidate package fits this repo as it stands. A native module in
   `modules/<slug>/` follows [architecture.md](../architecture.md) and the
   [Modules and Integrations ADR](../adr/0001-modules-and-integrations.md).
4. Two LMS apps for 2 courses and 2 enrolments is a consolidation
   question for a later, separately approved milestone. Nothing is cut over
   or decommissioned here.

## Sources

- WP-CLI plugin, theme and core lists, and aggregate database counts, on the
  TutorLMS host, 8 November 2026.
- A Moodle CLI script on the Moodle app (counts, enabled settings and
  additional plugins), 8 November 2026.
- Packagist: `https://repo.packagist.org/p2/<package>.json` and
  `https://packagist.org/api/security-advisories/`.
