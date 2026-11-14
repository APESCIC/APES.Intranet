<?php

namespace App\Core\Communications;

use App\Core\Communications\Contracts\PublishIntranetUpdate;
use App\Models\User;
use RuntimeException;

final class NullPublishIntranetUpdate implements PublishIntranetUpdate
{
    public function publish(User $author, string $title, string $body): int
    {
        throw new RuntimeException('The updates feed is not available.');
    }
}
