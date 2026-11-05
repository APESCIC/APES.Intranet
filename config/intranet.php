<?php

$csv = static fn (?string $value): array => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

return [

    /*
    | Local QA role switcher. Only works when this is true and APP_ENV is
    | local. Leave it off on Cloudron.
    */
    'qa_login_enabled' => (bool) env('QA_LOGIN_ENABLED', false),

    /*
    | HR and agreements pages ask for a new Cloudron sign-in when the last one
    | is older than this many minutes.
    */
    'fresh_signin_minutes' => max(1, (int) (env('FRESH_SIGNIN_MINUTES') ?: 15)),

    /*
    | Cloudron OIDC. The OIDC_* values win; otherwise the Cloudron addon
    | variables are used. Never commit real values.
    */
    'oidc' => [
        'issuer' => env('CLOUDRON_OIDC_ISSUER') ?: env('OIDC_ISSUER'),
        'client_id' => env('CLOUDRON_OIDC_CLIENT_ID') ?: env('OIDC_CLIENT_ID'),
        'client_secret' => env('CLOUDRON_OIDC_CLIENT_SECRET') ?: env('OIDC_CLIENT_SECRET'),
        'redirect_uri' => env('OIDC_REDIRECT_URI', rtrim((string) env('CLOUDRON_APP_ORIGIN', ''), '/').'/auth/callback'),
        'scopes' => $csv(env('OIDC_SCOPES', 'openid,profile,email')),
    ],

    /*
    | Cloudron LDAP, read-only, used to look up a person's groups at sign-in.
    */
    'ldap' => [
        'host' => env('CLOUDRON_LDAP_HOST') ?: env('LDAP_HOST'),
        'port' => (int) (env('CLOUDRON_LDAP_PORT') ?: env('LDAP_PORT', 389)),
        'base_dn' => env('CLOUDRON_LDAP_USERS_BASE_DN') ?: env('LDAP_BASE_DN'),
        'groups_base_dn' => env('CLOUDRON_LDAP_GROUPS_BASE_DN') ?: env('LDAP_GROUPS_BASE_DN'),
        'bind_dn' => env('CLOUDRON_LDAP_BIND_DN') ?: env('LDAP_BIND_DN'),
        'bind_password' => env('CLOUDRON_LDAP_BIND_PASSWORD') ?: env('LDAP_BIND_PASSWORD'),
        'user_filter' => env('LDAP_USER_FILTER', '(mail=%s)'),
        'group_attribute' => env('LDAP_GROUP_ATTRIBUTE', 'memberOf'),
        'timeout_seconds' => 5,
    ],

    /*
    | How long records are kept. A blank or too-small value falls back to a
    | safe minimum so a missing setting never wipes the log.
    */
    'retention' => [
        'audit_log_days' => max(30, (int) (env('AUDIT_LOG_RETENTION_DAYS') ?: 730)),
    ],

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

];
