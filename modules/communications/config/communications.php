<?php

return [
    'publish_permission' => 'comms.publish',
    'two_factor_nudge' => [
        'enabled' => true,
        'dismiss_days' => (int) env('COMMS_2FA_NUDGE_DISMISS_DAYS', 14),
    ],
    'engage_url' => env('COMMS_ENGAGE_URL', 'https://engage.cloud.microsoft/main/org/apescic.onmicrosoft.com/groups/apes'),
    'sharepoint_host' => env('COMMS_SHAREPOINT_HOST', 'https://apescic.sharepoint.com/'),
];
