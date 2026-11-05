<?php

namespace App\Core\Access;

/**
 * Common combinations of an account type and a permission role, from the
 * approved groups and permissions matrix (5 Nov 2026).
 */
final class RoleStacks
{
    /**
     * @return array<string, list<RoleName>> Stack key => roles.
     */
    public static function common(): array
    {
        return [
            'staff-hr' => [RoleName::Staff, RoleName::Hr],
            'staff-management' => [RoleName::Staff, RoleName::Management],
            'volunteer-supervisor' => [RoleName::Volunteer, RoleName::Supervisor],
            'staff-admin' => [RoleName::Staff, RoleName::Admin],
        ];
    }

    /**
     * @param  list<RoleName>  $roles
     */
    public static function label(array $roles): string
    {
        return implode(' + ', array_map(fn (RoleName $role) => $role->label(), $roles));
    }
}
