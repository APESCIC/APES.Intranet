<?php

$csv = static fn (?string $value): array => array_values(array_filter(array_map('trim', explode(',', (string) $value))));

return [

    /*
    | Local QA role switcher. Only works when this is true and APP_ENV is
    | local. Leave it off on Cloudron.
    */
    'qa_login_enabled' => (bool) env('QA_LOGIN_ENABLED', false),

    /*
    | Cloudron OIDC. The OIDC_* values win; otherwise the Cloudron addon
    | variables are used. Never commit real values.
    */
    'oidc' => [
        'issuer' => env('OIDC_ISSUER', env('CLOUDRON_OIDC_ISSUER')),
        'client_id' => env('OIDC_CLIENT_ID', env('CLOUDRON_OIDC_CLIENT_ID')),
        'client_secret' => env('OIDC_CLIENT_SECRET', env('CLOUDRON_OIDC_CLIENT_SECRET')),
        'redirect_uri' => env('OIDC_REDIRECT_URI', rtrim((string) env('CLOUDRON_APP_ORIGIN', ''), '/').'/auth/callback'),
        'scopes' => $csv(env('OIDC_SCOPES', 'openid,profile,email')),
    ],

    /*
    | Cloudron LDAP, read-only, used to look up a person's groups at sign-in.
    */
    'ldap' => [
        'host' => env('LDAP_HOST', env('CLOUDRON_LDAP_HOST')),
        'port' => (int) env('LDAP_PORT', env('CLOUDRON_LDAP_PORT', 389)),
        'base_dn' => env('LDAP_BASE_DN', env('CLOUDRON_LDAP_USERS_BASE_DN')),
        'groups_base_dn' => env('LDAP_GROUPS_BASE_DN', env('CLOUDRON_LDAP_GROUPS_BASE_DN')),
        'bind_dn' => env('LDAP_BIND_DN', env('CLOUDRON_LDAP_BIND_DN')),
        'bind_password' => env('LDAP_BIND_PASSWORD', env('CLOUDRON_LDAP_BIND_PASSWORD')),
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
    | Cloudron directory groups that grant an intranet role. Other groups are
    | ignored.
    */
    'group_roles' => [
        'intranet.staff' => 'staff',
        'intranet.volunteer' => 'volunteer',
        'intranet.student' => 'student',
        'intranet.manager' => 'manager',
        'intranet.hr' => 'hr',
        'intranet.legal' => 'legal',
        'intranet.admin' => 'admin',
    ],

];
