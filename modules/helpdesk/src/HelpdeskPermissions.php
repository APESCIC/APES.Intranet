<?php

namespace Modules\Helpdesk;

use App\Core\Access\RoleName;
use App\Models\User;
use Modules\Helpdesk\Models\Ticket;

final class HelpdeskPermissions
{
    public const QUEUE_IT = 'helpdesk.queue.it';

    public const QUEUE_MANAGEMENT = 'helpdesk.queue.management';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::QUEUE_IT => 'Work the IT help desk queue: see its tickets, reply, add private notes and assign',
            self::QUEUE_MANAGEMENT => 'Work the Management help desk queue: see its tickets, reply, add private notes and assign',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            RoleName::Management->value => [self::QUEUE_MANAGEMENT],
            RoleName::Admin->value => array_keys(self::permissions()),
        ];
    }

    public static function forQueue(string $queue): string
    {
        return match ($queue) {
            Ticket::QUEUE_IT => self::QUEUE_IT,
            Ticket::QUEUE_MANAGEMENT => self::QUEUE_MANAGEMENT,
        };
    }

    public static function works(User $user, string $queue): bool
    {
        return $user->can(self::forQueue($queue));
    }

    /**
     * The queues the person works, in menu order.
     *
     * @return list<string>
     */
    public static function queuesFor(User $user): array
    {
        return array_values(array_filter(array_keys(Ticket::QUEUES), fn (string $queue) => self::works($user, $queue)));
    }
}
