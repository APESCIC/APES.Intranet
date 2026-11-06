<?php

return [
    'publish_permission' => 'comms.publish',
    'manage_m365_permission' => 'comms.m365.manage',
    'two_factor_nudge' => [
        'enabled' => true,
        'dismiss_days' => (int) env('COMMS_2FA_NUDGE_DISMISS_DAYS', 14),
    ],
    'm365_url_allowlist' => [
        'microsoft.com',
        'cloud.microsoft',
        'office.com',
        'sharepoint.com',
    ],
    'engage_url' => env('COMMS_ENGAGE_URL', 'https://engage.cloud.microsoft/main/org/apescic.onmicrosoft.com/groups/apes'),
    'sharepoint_host' => env('COMMS_SHAREPOINT_HOST', 'https://apescic.sharepoint.com/'),
];
