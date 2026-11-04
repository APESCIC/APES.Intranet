<?php

namespace App\Core\Access;

/**
 * Core roles and base permissions. Modules add their own permissions when
 * they are built, and grant them to admin in their own seeders.
 */
final class AccessMatrix
{
    public const INTRANET_ACCESS = 'intranet.access';

    public const TEAM_MANAGE = 'team.manage';

    public const HR_ACCESS = 'hr.access';

    public const LEGAL_ACCESS = 'legal.access';

    public const ADMIN_ROLES_VIEW = 'admin.roles.view';

    public const ADMIN_AUDIT_VIEW = 'admin.audit.view';

    /**
     * @return array<string, string> Permission name => description.
     */
    public static function permissions(): array
    {
        return [
            self::INTRANET_ACCESS => 'Use the intranet',
            self::TEAM_MANAGE => 'Manage a team, rota or group of people',
            self::HR_ACCESS => 'Open the HR area',
            self::LEGAL_ACCESS => 'Open the agreements and legal area',
            self::ADMIN_ROLES_VIEW => 'View roles and permissions',
            self::ADMIN_AUDIT_VIEW => 'View the audit log',
        ];
    }

    /**
     * @return array<string, list<string>> Role name => permission names.
     */
    public static function grants(): array
    {
        return [
            RoleName::Staff->value => [self::INTRANET_ACCESS],
            RoleName::Volunteer->value => [self::INTRANET_ACCESS],
            RoleName::Student->value => [self::INTRANET_ACCESS],
            RoleName::Manager->value => [self::INTRANET_ACCESS, self::TEAM_MANAGE],
            RoleName::Hr->value => [self::INTRANET_ACCESS, self::HR_ACCESS],
            RoleName::Legal->value => [self::INTRANET_ACCESS, self::LEGAL_ACCESS],
            RoleName::Admin->value => array_keys(self::permissions()),
        ];
    }
}
