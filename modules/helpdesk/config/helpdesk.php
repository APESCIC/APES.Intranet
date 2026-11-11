<?php

return [
    // How many tickets one list shows before it stops.
    'list_limit' => 300,

    /*
    | Service level targets in wall-clock hours from when the ticket is
    | raised: the first reply from the queue, and resolving it.
    */
    'sla_hours' => [
        'urgent' => ['respond' => 4, 'resolve' => 24],
        'high' => ['respond' => 24, 'resolve' => 72],
        'normal' => ['respond' => 48, 'resolve' => 120],
        'low' => ['respond' => 120, 'resolve' => 240],
    ],
];
