<?php

namespace App\Core\Access;

use Modules\Hr\HrPermissions;

/**
 * Core Cloudron roles plus HR module permissions still granted from core roles.
 */
final class CoreAccessCatalog
{
    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return array_merge(AccessMatrix::permissions(), HrPermissions::permissions());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function roleGrants(): array
    {
        $merged = AccessMatrix::grants();

        foreach (HrPermissions::grants() as $roleName => $permissions) {
            $existing = $merged[$roleName] ?? [];
            $merged[$roleName] = array_values(array_unique(array_merge($existing, $permissions)));
        }

        return $merged;
    }
}
