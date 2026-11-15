<?php

return [
    'slug' => 'people',
    'name' => 'People',
    'kind' => 'module',
    'description' => 'Directory, profiles, placements and TOTP status.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => ['mysql', 'ldap'],
    'settings' => [],
];
