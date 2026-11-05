<?php

namespace App\Core\System\Checks;

use App\Core\Auth\Cloudron\LdapDirectoryGroups;

class LdapCheck implements AddonCheck
{
    public function __construct(private readonly LdapDirectoryGroups $directory) {}

    public function key(): string
    {
        return 'ldap';
    }

    public function configured(): bool
    {
        return filled(config('intranet.ldap.host')) && filled(config('intranet.ldap.bind_dn'));
    }

    public function probe(): void
    {
        $this->directory->checkBind();
    }

    public function details(): array
    {
        return [
            'host' => (string) config('intranet.ldap.host'),
            'port' => (string) config('intranet.ldap.port'),
            'database' => null,
        ];
    }
}
