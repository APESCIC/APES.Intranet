<?php

return [
    'slug' => 'communications',
    'name' => 'Communications',
    'kind' => 'module',
    'description' => 'Updates, handbook, events and policies. Microsoft 365 lives in its own Integration.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => ['mysql', 'sendmail'],
    'settings' => [],
];
