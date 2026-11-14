# DPIA: HR records

| Item | Value |
| --- | --- |
| Status | **Approved by the owner on 14 November 2026** (v0.6.0 plan approval for shipping this assessment with the HR build) |
| Controller | Association of Protecting Exotic Species CIC (APES CIC) |
| Owner and approver | Bambie Murphy, Director |
| Feature | Intranet HR records: core record, DBS and right-to-work tracking, leave and absence, onboarding and offboarding checklists, training requirements, appraisals and recognition (v0.6.0, Epic #47, issue #48) |
| Template | ICO sample DPIA template, steps 1 to 7 |
| Drafted | 14 November 2026 |
| Review | Before real (non-demo) HR data is stored in production, after any change to categories of data or access, and once a year while HR features are live |

This assessment is kept in the repository so it can be reviewed with the code
([where documents live](../decisions.md#where-documents-live)). It is **not** filed in SharePoint.
Internal hosting detail that must stay private can sit in
`APESCIC/.github-private` under `docs/apes-intranet/`; this public copy leaves
out Cloudron app IDs and similar inventory.

Retention periods that the app enforces (or will enforce when purge jobs land)
are in `config/intranet.php` under `retention.hr`.

## Step 1: Why a DPIA is needed

APES CIC will keep HR information about staff, volunteers and students in the
intranet so HR and managers can run checks, leave, training and appraisals.
That includes special category data (DBS outcomes as categories of check and
status, not certificate scans) and information about people’s working lives.

The ICO lists processing of special category data and systematic monitoring of
employees among processing that is likely to need a DPIA. Volunteers and
students are included. APES CIC has therefore decided that a DPIA is required
before HR features hold real records ([decisions](../decisions.md#features)).

## Step 2: The processing

### Nature

1. An authorised HR, management or admin user opens a member’s HR record after
   a fresh Cloudron sign-in.
2. The app shows fields the role is allowed to see. Every view of an HR record
   (and every view of special category fields) is written to the audit log
   without storing the field contents in the audit entry
   ([architecture](../architecture.md)).
3. Changes to HR fields are saved in the Cloudron app’s MySQL database on the
   London server only. Document **scans are not stored** in the intranet: DBS
   and right-to-work evidence stays as links to SharePoint (or another agreed
   file store), not as files in this repository or in `/app/data`.
4. Leave requests, absence notes, training completion, appraisals and
   recognition are created and updated through the same access rules.
5. Reminders (for example DBS expiry) may be sent through the intranet’s
   notification channels. Wording for lock-screen push stays generic where
   push is used.

### Scope

| Area | What is kept | What is not kept |
| --- | --- | --- |
| HR record | Role-appropriate employment/volunteering fields, contacts for HR use, links to external docs | Payroll; document binary files |
| DBS / right-to-work | Check type, dates, reference, expiry, status; SharePoint link only | Certificate or passport scans |
| Leave and absence | Requests, approvals, balances, HR absence notes | Pay calculations |
| Onboarding / offboarding | Checklist progress; offboarding may open an IT helpdesk ticket | Automatic Cloudron account deletion |
| Training | Required courses per role, completion status, LMS links | Course content (Moodle / later Learning milestones) |
| Appraisals | Cycle, self/manager review, objectives, sign-off | Pay decisions |
| Recognition | Message, optional updates-feed post | Rewards or points |

- **People:** APES CIC staff, volunteers and students with intranet accounts.
- **Special category:** DBS-related status and similar safeguarding checks are
  treated as special category and opened only through `SensitiveData` with a
  fresh sign-in and an audit entry.
- **Volume:** one HR record per member; a small number of checks, leave rows
  and appraisals per year.
- **Area:** processing for APES CIC’s UK operations.

### Context

- People are in a working or volunteering relationship, so access must be
  limited to HR, management and admin roles with `hr.access` (and special
  category view where needed). Non-HR roles get 403 on HR pages.
- Fake demo data only in local and CI. Real HR data must not enter the public
  repository or agent chats.
- Some members may be under 18 (students/volunteers). The same access and
  audit rules apply; no extra categories of data are collected for them in
  this version beyond what the role needs.

### Purposes

- Lawful HR administration: right-to-work and safeguarding checks, leave,
  training compliance, appraisals and recognition.
- Benefit to members: clear records, timely reminders, consistent onboarding
  and exit, fair reviews.

### Hosting and security evidence (14 November 2026)

- Official HR application data lives on the Cloudron server in **London**
  only, not in SharePoint as the system of record for these fields.
- Disk encryption of this Linode was confirmed by Bambie on 12 November 2026
  (Europe/London).
- Cloudron backups stay in the current Paris bucket, kept for 30 days, with
  Cloudron AES-256 backup encryption and file-name encryption, plus a restore
  test.
- Hardening from the 12 November 2026 IT Admin review is a **v0.7.0 go-live
  prerequisite** before real documents are relied on beside the app, and runs
  alongside the build.
- Do **not** claim SOC 2 Type II, SOC 1, SOC 3, CSA STAR or HDS for this
  hosting. Cyber Essentials covers Akamai’s UK offices and staff, not this
  server.

## Step 3: Consultation

- **Owner / Director:** approved this DPIA with the v0.6.0 shipping plan on
  14 November 2026.
- **HR and management:** will use the features; access matrix already decided
  in Cloudron groups (`intranet.hr`, `intranet.management`, `intranet.admin`).
- **Members:** informed through privacy notice updates when real data goes
  live; local demo uses fake accounts only.
- **Data protection advice:** APES CIC does not have a statutory DPO. The
  Director decides, and may ask for outside advice before real data is loaded.

## Step 4: Necessity and proportionality

- **Lawful bases (summary):**
  - Contract / steps prior to contract and legitimate interests for ordinary
    HR administration (UK GDPR Article 6).
  - Employment, social security and social protection law and/or substantial
    public interest / safeguarding where special category data is needed for
    DBS-related tracking (Article 9), limited to status metadata and links,
    not certificate images in the app.
  - Right-to-work checks: legal obligation to prevent illegal working.
- **Minimisation:** no document scans in the app; field-level access; fresh
  sign-in; audit of views; fake data in development.
- **Retention:** periods in `config/intranet.php` `retention.hr` (see Step 7).
  Purge jobs may follow in later issues; the periods are agreed now.
- **Processors and transfers:** Cloudron hosting and backups as above. No new
  third-party HR SaaS in v0.6.0. LMS links point at the existing APES LMS.

## Step 5: Risks

| Risk to people | Likelihood | Severity | Overall |
| --- | --- | --- | --- |
| Unauthorised staff see HR or DBS status | Possible | Severe | High |
| Special category data copied into tickets, chat or the public repo | Possible | Severe | High |
| Excessive retention of checks or appraisals | Possible | Significant | Medium |
| Inaccurate DBS/right-to-work status leads to unfair decisions | Possible | Significant | Medium |
| Push/email reminders reveal sensitive detail on a lock screen | Possible | Significant | Medium |
| Real data used in demos or CI | Remote | Severe | Medium |

## Step 6: Measures to reduce risk

| Risk | Measure | Effect | Residual risk | Approved |
| --- | --- | --- | --- | --- |
| Unauthorised access | `hr.access` / special category gates, fresh sign-in, 403 tests | Reduced | Low | Owner |
| Data leakage to public channels | No scans in app; audit without contents; repo/agent rules: fake data only | Reduced | Low | Owner |
| Excessive retention | Periods in config; review yearly | Reduced | Low | Owner |
| Inaccurate status | HR-maintained fields; expiry reminders; reports of missing/expired checks | Reduced | Low | Owner |
| Sensitive notifications | Generic lock-screen wording where push is used; detail in-app | Reduced | Low | Owner |
| Demo/CI exposure | Demo seeders and tests use `@example.test` only | Reduced | Low | Owner |

## Step 7: Sign-off and outcomes

| Item | Name or date | Notes |
| --- | --- | --- |
| Measures approved by | Bambie Murphy, Director | 14 November 2026 |
| Residual risks approved by | Bambie Murphy, Director | Residual risks low after measures; no ICO consultation needed for this scoped build |
| Decision | Approved | Retention periods below; features may be built under v0.6.0 |
| Approval date | 2026-11-14 | Recorded with the approved shipping plan on Epic #47 |
| Filed | This repository path only | Not SharePoint |
| Review date | 14 November 2027 | Or sooner before loading real production HR data, or on scope change |

### Agreed retention (config keys)

Values are years after the member leaves (or rolling years for active leave
records), overridable by environment variables. Safe minimums apply if unset.

| Record | Default | Env override |
| --- | --- | --- |
| Core HR record | 6 years after leave | `HR_RETENTION_RECORD_YEARS_AFTER_LEAVE` |
| DBS check metadata | 6 years after leave | `HR_RETENTION_DBS_YEARS_AFTER_LEAVE` |
| Right-to-work check metadata | 2 years after leave | `HR_RETENTION_RIGHT_TO_WORK_YEARS_AFTER_LEAVE` |
| Leave and absence | 3 years | `HR_RETENTION_LEAVE_YEARS` |
| Appraisals | 6 years after leave | `HR_RETENTION_APPRAISAL_YEARS_AFTER_LEAVE` |
| Training completion | 6 years after leave | `HR_RETENTION_TRAINING_YEARS_AFTER_LEAVE` |
| Onboarding / offboarding checklists | 2 years after leave | `HR_RETENTION_CHECKLIST_YEARS_AFTER_LEAVE` |
| Recognition posts | 2 years after leave | `HR_RETENTION_RECOGNITION_YEARS_AFTER_LEAVE` |

Audit log retention stays under `retention.audit_log_days` (existing setting).
