<?php

namespace Modules\Operations;

use App\Core\Access\RoleName;

final class OperationsPermissions
{
    public const SITES_MANAGE = 'operations.sites.manage';

    public const ROTA_MANAGE = 'operations.rota.manage';

    public const TASKS_MANAGE = 'operations.tasks.manage';

    public const HOURS_EXPORT = 'operations.hours.export';

    public const FORMS_MANAGE = 'operations.forms.manage';

    public const CHECKLISTS_MANAGE = 'operations.checklists.manage';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::SITES_MANAGE => 'Manage operations sites and their geofence areas',
            self::ROTA_MANAGE => 'Build and publish rotas and shift templates: own team for supervisors, every team for management and admin',
            self::TASKS_MANAGE => 'Assign tasks to people, teams and shifts',
            self::HOURS_EXPORT => 'See and export volunteer hours totals',
            self::FORMS_MANAGE => 'Build, publish and export forms',
            self::CHECKLISTS_MANAGE => 'Set up daily checklist templates: own team for supervisors, every team for management and admin',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            RoleName::Supervisor->value => [self::ROTA_MANAGE, self::TASKS_MANAGE, self::CHECKLISTS_MANAGE],
            RoleName::Management->value => [self::ROTA_MANAGE, self::TASKS_MANAGE, self::HOURS_EXPORT, self::CHECKLISTS_MANAGE],
            RoleName::Hr->value => [self::HOURS_EXPORT],
            RoleName::Admin->value => array_keys(self::permissions()),
        ];
    }
}
