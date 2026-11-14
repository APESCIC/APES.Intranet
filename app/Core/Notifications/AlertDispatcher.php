<?php

namespace App\Core\Notifications;

use App\Core\Notifications\Notifications\IntranetAlert;
use App\Models\User;

/**
 * Send generic push / in-app alerts without ticket or message content.
 */
final class AlertDispatcher
{
    /**
     * @param  iterable<User|null>  $people
     */
    public static function send(
        iterable $people,
        NotificationCategory $category,
        string $title,
        string $body,
        ?string $url = null,
        ?string $ticketReference = null,
    ): void {
        $sent = [];

        foreach ($people as $person) {
            if (! $person instanceof User || isset($sent[$person->getKey()])) {
                continue;
            }

            $sent[$person->getKey()] = true;
            $person->notify(new IntranetAlert($category, $title, $body, $url, $ticketReference));
        }
    }
}
