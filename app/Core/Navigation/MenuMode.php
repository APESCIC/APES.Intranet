<?php

namespace App\Core\Navigation;

/**
 * The sidebar panel. The route decides it: admin.* pages are the Admin panel,
 * everything else is the Staff panel. Nothing is stored in the session.
 */
enum MenuMode: string
{
    case Staff = 'staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Staff => 'Staff',
            self::Admin => 'Admin',
        };
    }
}
