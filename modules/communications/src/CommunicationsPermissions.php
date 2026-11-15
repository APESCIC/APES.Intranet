<?php

namespace Modules\Communications;

use App\Core\Access\RoleName;

final class CommunicationsPermissions
{
    public const PUBLISH = 'comms.publish';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::PUBLISH => 'Publish updates, events and handbook chapters',
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
            RoleName::Admin->value => [self::PUBLISH],
        ];
    }
}
