<?php

namespace App\Core\Auth\Contracts;

use App\Core\Auth\Exceptions\DirectoryUnavailable;

interface DirectoryGroups
{
    /**
     * Lower-case group names (cn) for the person with this email. Empty when
     * the person is not in the directory or has no groups.
     *
     * @return list<string>
     *
     * @throws DirectoryUnavailable
     */
    public function groupsForEmail(string $email): array;
}
