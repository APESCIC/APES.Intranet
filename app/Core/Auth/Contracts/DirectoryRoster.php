<?php

namespace App\Core\Auth\Contracts;

use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\Exceptions\DirectoryUnavailable;

/**
 * Read-only list of people and their intranet groups from the directory.
 */
interface DirectoryRoster
{
    /**
     * @return list<DirectoryPerson>
     *
     * @throws DirectoryUnavailable
     */
    public function listPeople(): array;
}
