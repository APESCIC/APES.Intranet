<?php

namespace App\Core\Communications\Contracts;

use App\Models\User;

/**
 * Publish a short update to the staff feed. Implemented by Communications; HR
 * uses this for optional recognition posts so it never imports that module.
 */
interface PublishIntranetUpdate
{
    /**
     * @return int The new update post id
     */
    public function publish(User $author, string $title, string $body): int;
}
