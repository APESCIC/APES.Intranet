<?php

return [
    'slug' => 'helpdesk',
    'name' => 'Help desk',
    'kind' => 'module',
    'description' => 'Tickets, SLA and identity-check support for members.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => ['mysql', 'sendmail'],
    'settings' => [],
];
