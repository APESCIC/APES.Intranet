<?php

namespace App\Core\Auth;

final class GroupRoleMapper
{
    /**
     * The approved Cloudron groups, in config order.
     *
     * @return list<string>
     */
    public function approvedGroups(): array
    {
        return array_keys(config('intranet.group_roles', []));
    }

    /**
     * Intranet role names for a person's directory groups, ignoring any group
     * that is not one of the approved groups.
     *
     * @param  list<string>  $groups
     * @return list<string>
     */
    public function rolesFor(array $groups): array
    {
        /** @var array<string, string> $map */
        $map = config('intranet.group_roles', []);
        $roles = [];

        foreach ($groups as $group) {
            $role = $map[strtolower(trim($group))] ?? null;

            if ($role !== null) {
                $roles[] = $role;
            }
        }

        return array_values(array_unique($roles));
    }
}
