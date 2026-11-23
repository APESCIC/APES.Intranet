<?php

namespace Modules\Hr;

use App\Core\Access\RoleName;

/**
 * Spatie permission names for the HR module (records, onboarding, DBS).
 */
final class HrPermissions
{
    public const ACCESS = 'hr.access';

    public const SPECIAL_CATEGORY_VIEW = 'hr.special_category.view';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::ACCESS => 'Open the HR area',
            self::SPECIAL_CATEGORY_VIEW => 'See DBS and health information (special category data, every view audited)',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        return [
            RoleName::Management->value => [self::ACCESS, self::SPECIAL_CATEGORY_VIEW],
            RoleName::Hr->value => [self::ACCESS, self::SPECIAL_CATEGORY_VIEW],
            RoleName::Admin->value => [self::ACCESS, self::SPECIAL_CATEGORY_VIEW],
        ];
    }
}
