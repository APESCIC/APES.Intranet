<?php

namespace App\Core\Notifications;

enum NotificationCategory: string
{
    case Tickets = 'tickets';
    case AccountSecurity = 'account_security';
    case Announcements = 'announcements';
    case Operations = 'operations';

    public function label(): string
    {
        return match ($this) {
            self::Tickets => 'Tickets',
            self::AccountSecurity => 'Account and security',
            self::Announcements => 'Announcements',
            self::Operations => 'Rota and operations',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Tickets => 'New tickets, assignments and replies.',
            self::AccountSecurity => 'Access reviews and account alerts. These can still arrive during quiet hours.',
            self::Announcements => 'Intranet announcements you have opted in to.',
            self::Operations => 'Rota and other operations notices.',
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        return [
            self::Tickets,
            self::AccountSecurity,
            self::Announcements,
            self::Operations,
        ];
    }
}
