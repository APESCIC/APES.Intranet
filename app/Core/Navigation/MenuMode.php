<?php

namespace App\Core\Navigation;

enum MenuMode: string
{
    case Normal = 'normal';
    case Admin = 'admin';

    public const SESSION_KEY = 'intranet.menu_mode';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Admin => 'Admin',
        };
    }
}
