<?php

namespace Modules\Operations\Support;

use App\Core\Notifications\AlertDispatcher;
use App\Core\Notifications\NotificationCategory;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Modules\Operations\Mail\OperationsNoticeMail;

final class Notifier
{
    /**
     * @param  iterable<User|null>  $people
     * @param  list<string>  $lines
     */
    public static function send(iterable $people, string $subject, array $lines, ?string $url = null): void
    {
        $sent = [];

        foreach ($people as $person) {
            if ($person === null || blank($person->email) || isset($sent[$person->getKey()])) {
                continue;
            }

            $sent[$person->getKey()] = true;
            Mail::to($person->email)->send(new OperationsNoticeMail($subject, $lines, $url));
        }

        AlertDispatcher::send(
            $people,
            NotificationCategory::Operations,
            'You have a new rota or operations notice.',
            'Open the intranet to view it.',
            $url,
        );
    }
}
