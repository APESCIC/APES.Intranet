<?php

namespace App\Core\Auth;

/**
 * One person as read from Cloudron LDAP (or a test fake).
 */
final readonly class DirectoryPerson
{
    /**
     * @param  list<string>  $groups
     */
    public function __construct(
        public string $email,
        public string $name,
        public ?string $username,
        public array $groups,
        public bool $active = true,
    ) {}
}
