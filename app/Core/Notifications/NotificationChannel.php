<?php

namespace App\Core\Notifications;

enum NotificationChannel: string
{
    case Push = 'push';
    case Email = 'email';
    case InApp = 'in_app';

    public function label(): string
    {
        return match ($this) {
            self::Push => 'Browser push',
            self::Email => 'Email',
            self::InApp => 'In-app bell',
        };
    }
}
