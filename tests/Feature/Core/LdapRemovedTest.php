<?php

use App\Core\Auth\Cloudron\CloudronApiDirectoryRoster;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\System\CloudronAddons;

it('has no LDAP code, config or directory switch left', function () {
    expect(config('intranet.ldap'))->toBeNull()
        ->and(config('intranet.directory_source'))->toBeNull()
        ->and(class_exists('App\\Core\\Auth\\Cloudron\\LdapDirectoryGroups', false))->toBeFalse()
        ->and(class_exists('App\\Core\\System\\Checks\\LdapCheck', false))->toBeFalse()
        ->and(class_exists('App\\Core\\Auth\\DirectorySource', false))->toBeFalse()
        ->and(array_key_exists('ldap', CloudronAddons::definitions()))->toBeFalse()
        ->and(CloudronAddons::FORBIDDEN_ENV_KEYS)->toContain('LDAP_BIND_PASSWORD')
        ->and(app(DirectoryRoster::class))->toBeInstanceOf(CloudronApiDirectoryRoster::class);
});
