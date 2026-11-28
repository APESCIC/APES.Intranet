<?php

use App\Core\System\CloudronAddons;

$csv = static fn (?string $value): array => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

return [

    /*
    | Local QA role switcher. Only works when this is true and APP_ENV is
    | local. Leave it off on Cloudron.
    */
    'qa_login_enabled' => (bool) env('QA_LOGIN_ENABLED', false),

    /*
    | Organisation Director email. Gates "Set as shared default" on the admin
    | dashboard layout. Not a Spatie role — compare case-insensitively to the
    | signed-in user's email. Name only in .env.example; value in APES Vault.
    */
    'director_email' => env('INTRANET_DIRECTOR_EMAIL'),

    /*
    | HR and agreements pages ask for a new Cloudron sign-in when the last one
    | is older than this many minutes.
    */
    'fresh_signin_minutes' => max(1, (int) (env('FRESH_SIGNIN_MINUTES') ?: 15)),

    /*
    | Cloudron OIDC. CLOUDRON_OIDC_* (addon) is preferred; OIDC_* is the
    | fallback for local and hand-set shared .env. Never commit real values.
    */
    'oidc' => [
        'issuer' => env('CLOUDRON_OIDC_ISSUER') ?: env('OIDC_ISSUER'),
        'client_id' => env('CLOUDRON_OIDC_CLIENT_ID') ?: env('OIDC_CLIENT_ID'),
        'client_secret' => env('CLOUDRON_OIDC_CLIENT_SECRET') ?: env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI', rtrim((string) env('CLOUDRON_APP_ORIGIN', ''), '/').'/auth/callback'),
        'scopes' => $csv(env('OIDC_SCOPES', 'openid,profile,email,groups')),
    ],

    /*
    | Names (never values) of the addon variables that are set, worked out
    | when the config is cached, for Admin > System status.
    */
    'cloudron_variables' => array_values(array_filter(
        array_merge(...array_column(CloudronAddons::definitions(), 'variables')),
        fn (string $name) => filled(env($name)),
    )),

    /*
    | How long records are kept. A blank or too-small value falls back to a
    | safe minimum so a missing setting never wipes the log. HR and Learning
    | periods are years (see docs/dpia/hr-records.md and
    | docs/dpia/learning-records.md); audit log stays in days.
    */
    'retention' => [
        'audit_log_days' => max(30, (int) (env('AUDIT_LOG_RETENTION_DAYS') ?: 730)),
        'hr' => [
            'record_years_after_leave' => max(1, (int) (env('HR_RETENTION_RECORD_YEARS_AFTER_LEAVE') ?: 6)),
            'dbs_years_after_leave' => max(1, (int) (env('HR_RETENTION_DBS_YEARS_AFTER_LEAVE') ?: 6)),
            'right_to_work_years_after_leave' => max(1, (int) (env('HR_RETENTION_RIGHT_TO_WORK_YEARS_AFTER_LEAVE') ?: 2)),
            'leave_years' => max(1, (int) (env('HR_RETENTION_LEAVE_YEARS') ?: 3)),
            'appraisal_years_after_leave' => max(1, (int) (env('HR_RETENTION_APPRAISAL_YEARS_AFTER_LEAVE') ?: 6)),
            'training_years_after_leave' => max(1, (int) (env('HR_RETENTION_TRAINING_YEARS_AFTER_LEAVE') ?: 6)),
            'checklist_years_after_leave' => max(1, (int) (env('HR_RETENTION_CHECKLIST_YEARS_AFTER_LEAVE') ?: 2)),
            'recognition_years_after_leave' => max(1, (int) (env('HR_RETENTION_RECOGNITION_YEARS_AFTER_LEAVE') ?: 2)),
        ],
        'learning' => [
            'enrolment_years_after_end' => max(1, (int) (env('LEARNING_RETENTION_ENROLMENT_YEARS_AFTER_END') ?: 6)),
            'progress_years_after_end' => max(1, (int) (env('LEARNING_RETENTION_PROGRESS_YEARS_AFTER_END') ?: 6)),
            'submission_years_after_end' => max(1, (int) (env('LEARNING_RETENTION_SUBMISSION_YEARS_AFTER_END') ?: 6)),
            'grade_years_after_end' => max(1, (int) (env('LEARNING_RETENTION_GRADE_YEARS_AFTER_END') ?: 6)),
            'assignment_years_after_end' => max(1, (int) (env('LEARNING_RETENTION_ASSIGNMENT_YEARS_AFTER_END') ?: 2)),
            'course_version_years_after_archive' => max(1, (int) (env('LEARNING_RETENTION_COURSE_VERSION_YEARS_AFTER_ARCHIVE') ?: 6)),
        ],
    ],

    /*
    | Folder of reviewed release notes, one vX.Y.Z.json per release, that
    | changelog:sync copies into the change log hub (docs/release-notes.md).
    */
    'changelog_path' => base_path('changelog/releases'),

    /*
    | The 8 approved Cloudron directory groups and the role each one grants
    | (decided by Bambie, 5 Nov 2026). Only these grant access: any other
    | group, including other intranet.* names, is ignored, and a person in
    | none of them sees the "No intranet access" page.
    */
    'group_roles' => [
        'intranet.staff' => 'staff',
        'intranet.volunteer' => 'volunteer',
        'intranet.student' => 'student',
        'intranet.supervisor' => 'supervisor',
        'intranet.management' => 'management',
        'intranet.hr' => 'hr',
        'intranet.legal' => 'legal',
        'intranet.admin' => 'admin',
    ],

    /*
    | Cloudron dashboard links and optional read-only API access. The API
    | token is for the 2FA status view and (from v0.6.19) directory sync;
    | never commit a real value.
    */
    'cloudron' => [
        'profile_url' => env('CLOUDRON_PROFILE_URL', 'https://my.cloudron.apes.org.uk/#/profile'),
        'api_base' => env('CLOUDRON_API_BASE', 'https://my.cloudron.apes.org.uk'),
        'api_token' => env('CLOUDRON_API_TOKEN'),
    ],

];
