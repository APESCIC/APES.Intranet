<?php

return [
    'geofence' => [
        // Stays off until the DPIA (docs/dpia/clock-in-geofence.md) is approved
        // and its approval date is recorded below. Both must be set.
        'enabled' => (bool) env('OPERATIONS_GEOFENCE_ENABLED', false),
        'dpia_approved_at' => env('OPERATIONS_GEOFENCE_DPIA_APPROVED_AT'),
        'default_radius_metres' => (int) env('OPERATIONS_GEOFENCE_RADIUS_METRES', 250),
    ],
    'clock' => [
        'missed_clock_out_grace_minutes' => (int) env('OPERATIONS_MISSED_CLOCK_OUT_GRACE_MINUTES', 60),
        'missed_clock_out_unscheduled_hours' => (int) env('OPERATIONS_MISSED_CLOCK_OUT_UNSCHEDULED_HOURS', 12),
    ],
    'uploads' => [
        'disk' => 'local',
        'max_kilobytes' => (int) env('OPERATIONS_UPLOAD_MAX_KB', 8192),
    ],
];
