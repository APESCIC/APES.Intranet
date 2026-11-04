<?php

namespace Tests\Fakes;

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Exceptions\DirectoryUnavailable;

final class FakeDirectoryGroups implements DirectoryGroups
{
    /** @var list<string> */
    public array $groups = [];

    public bool $unavailable = false;

    public function groupsForEmail(string $email): array
    {
        if ($this->unavailable) {
            throw new DirectoryUnavailable('down');
        }

        return $this->groups;
    }
}
