<?php

namespace Modules\Communications;

use App\Core\Access\RoleName;

final class CommunicationsPermissions
{
    public const PUBLISH = 'comms.publish';

    public const MANAGE_M365 = 'comms.m365.manage';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::PUBLISH => 'Publish updates, events and handbook chapters',
            self::MANAGE_M365 => 'Manage Microsoft 365 link registry',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            RoleName::Management->value => [self::PUBLISH],
            RoleName::Hr->value => [self::PUBLISH],
            RoleName::Admin->value => [self::PUBLISH, self::MANAGE_M365],
        ];
    }
}
