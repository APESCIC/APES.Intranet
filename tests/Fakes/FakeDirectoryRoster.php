<?php

namespace Tests\Fakes;

use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\Exceptions\DirectoryUnavailable;

final class FakeDirectoryRoster implements DirectoryRoster
{
    /** @var list<DirectoryPerson> */
    public array $people = [];

    public bool $unavailable = false;

    public function listPeople(): array
    {
        if ($this->unavailable) {
            throw new DirectoryUnavailable('down');
        }

        return $this->people;
    }
}
