<?php

return [
    /*
    | Learning Module settings. Course authoring, enrolment and the player
    | land in later v0.6.6 issues. Gradebook UI is v0.6.7.
    */
    'reports' => [
        // Hide grade distributions when fewer than this many marks exist.
        'min_group_size' => (int) env('LEARNING_REPORT_MIN_GROUP_SIZE', 5),
    ],
];
