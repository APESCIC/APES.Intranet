<?php

namespace Modules\Microsoft365;

use App\Core\Access\RoleName;

final class Microsoft365Permissions
{
    /**
     * Kept as comms.m365.manage so existing role grants keep working after the extract.
     */
    public const MANAGE = 'comms.m365.manage';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::MANAGE => 'Manage Microsoft 365 link registry',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            RoleName::Admin->value => [self::MANAGE],
        ];
    }
}
