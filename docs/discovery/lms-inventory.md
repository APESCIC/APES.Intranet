# LMS content and standards inventory

Discovery for [#155](https://github.com/APESCIC/APES.Intranet/issues/155),
part of milestone v0.3.5 Platform discovery (Epic
[#154](https://github.com/APESCIC/APES.Intranet/issues/154)). Checked on
8 November 2026.

This inventory does **not** authorise any build work. A Training Module, a
cutover or a decommission each needs Bambie's separate approval of a build
milestone.

## How it was checked

- Read-only `cloudron exec` commands on the two LMS apps: plugin and theme
  lists, and aggregate counts from the databases. Each script was pushed to
  `/tmp`, run once and deleted.
- Moodle was read through its own API (`core_plugin_manager` and `$DB`
  counts), run as `www-data`, so nothing was written apart from Moodle's
  normal cache.
- No names, email addresses, grades, enrolment rows or configuration secrets
  were read into this document, the issue or the chat. Counts only.
- Packagist metadata and the Packagist security advisories API for package
  candidates. No package was installed.

## Summary

- APES has **two** LMS apps on Cloudron, not one. The separate Moodle is
  **confirmed**.
- The TutorLMS host that the README links to has **no courses**. The only
  course content is on the Moodle at `upskilling.intranet.apes.org.uk`.
- Both are small: Moodle has 2 courses, 19 pages, 2 quizzes and 32
  multiple-choice questions, with 2 enrolments and no completions.
- **None** of SCORM, H5P, xAPI or LTI is in use on either app.
- Both apps sign in through Cloudron (OpenID Connect or OAuth 2).

## App 1: TutorLMS on WordPress

| Fact | Value |
| --- | --- |
| Location | <https://lms.intranet.apes.org.uk/> |
| Cloudron app | `5f6f9dc8-1bd6-4cdb-a988-a6c7dcfbdf93`, package `org.wordpress.cloudronapp` 3.21.1 |
| WordPress | 7.1.4 |
| TutorLMS | 4.2.0 (free edition, GPLv3) |
| Theme | Twenty Twenty-Five 1.5 (active); Twenty Twenty-Four and Twenty Twenty-Three inactive |
| Sign-in | `cloudron-sso` 1.0.0 and `daggerhart-openid-connect-generic` 3.11.3, both active (Cloudron OIDC) |
| Accounts | 1 (administrator and Tutor instructor) |

Plugins:

| Plugin | Version | Status |
| --- | --- | --- |
| `tutor` (TutorLMS) | 4.2.0 | Active |
| `certificate-customizer-for-tutor-lms` | 1.0.1 | Active |
| `cloudron-sso` | 1.0.0 | Active |
| `daggerhart-openid-connect-generic` | 3.11.3 | Active |
| `smtp-mailer` | 1.1.28 | Active |
| `tutor-lms-divi-modules` | 4.0.0 | Inactive |
| `tutor-lms-elementor-addons` | 4.0.2 | Inactive |
| `wp-megamenu` | 1.4.2 | Inactive |
| `akismet` | 5.7.2 | Inactive |
| `hello` | 1.7.2 | Inactive |

Content (counts):

| Item | Count |
| --- | --- |
| Courses, lessons, topics, quizzes, assignments | 0 (no Tutor course post types exist) |
| Quiz questions and attempts | 0 |
| Orders, carts, coupons, earnings, withdrawals | 0 (monetisation unused) |
| Pages | 5 published, 2 in the bin |
| Posts | 2, both in the bin |
| Media library items | 0 |

There is no TutorLMS Pro, and no SCORM, H5P, xAPI or LTI plugin installed.

## App 2: Moodle ("Upskilling")

| Fact | Value |
| --- | --- |
| Location | <https://upskilling.intranet.apes.org.uk/> |
| Cloudron app | `d35884ef-02e2-4ce6-a975-cacc8aee9069`, package `org.moodle.cloudronapp` 4.4.0 |
| Moodle | 5.3 (Build: 20261005). The package number is not the Moodle release. |
| Theme | Boost |
| Sign-in | OAuth 2 only (`auth_enabled=oauth2`), with one issuer, "Cloudron" (`my.cloudron.apes.org.uk`), shown on the login page |
| Accounts | 2, not counting guest (1 OAuth 2 account and the local admin). Both active in the last 90 days. |
| Enrolment methods enabled | Manual, guest, self, cohort |
| Completion tracking | Enabled |
| Competencies | Enabled, 0 frameworks |
| Badges | 0 |
| Web services | Enabled |
| Additional plugins | Only `theme_classic` (disabled). It is listed as additional because Moodle 5 removed it from core. |

Content (counts):

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
| H5P | No plugin | 0 H5P activities and 0 deployed H5P items. The 146 H5P libraries are Moodle's bundled core set, not content. | **Not used** |
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
| `cms-orbit/lms` | 4.6.1 (Sep 2026) | MIT | PHP ^8.4, Laravel ^13, `cms-orbit/core` ^4.6 | **Rejected.** Coupled to the Orbit CMS. Its PHP ^8.4 floor would also break local Laragon (PHP 8.3). |
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

- `wp plugin list`, `wp theme list`, `wp core version` and aggregate
  `wp db query` counts on `lms.intranet.apes.org.uk`, 8 November 2026.
- A Moodle CLI script on `upskilling.intranet.apes.org.uk` (counts, enabled
  settings, `core_plugin_manager` additional plugins, OAuth 2 issuer name and
  host), 8 November 2026.
- Public TutorLMS readme: <https://lms.intranet.apes.org.uk/wp-content/plugins/tutor/readme.txt>.
- Packagist: `https://repo.packagist.org/p2/<package>.json` and
  `https://packagist.org/api/security-advisories/`.
