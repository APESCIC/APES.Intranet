<?php

namespace Modules\People\Support;

use App\Core\Access\AccessMatrix;
use App\Models\User;

/**
 * Which profile fields a viewer may see or edit.
 */
final class ProfileFields
{
    /** Fields any signed-in member may see on an active directory profile. */
    public const PUBLIC = [
        'name',
        'email',
        'job_title',
        'team',
        'phone',
        'mobile',
        'location',
        'skills',
        'bio',
        'photo_path',
        'primary_role',
        'account_type',
    ];

    /** Fields only HR, management and admin may see. */
    public const HR_ONLY = [
        'emergency_contact_name',
        'emergency_contact_phone',
        'employee_number',
        'totp_enabled',
    ];

    /** Fields a person may edit on their own profile. */
    public const OWN_EDITABLE = [
        'job_title',
        'team',
        'phone',
        'mobile',
        'location',
        'skills',
        'bio',
        'photo',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    /** Own fields that only HR-capable roles may edit on themselves. */
    public const OWN_HR_EDITABLE = [
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    public static function canViewHrFields(User $viewer): bool
    {
        return $viewer->can(AccessMatrix::HR_ACCESS);
    }

    /**
     * @return list<string>
     */
    public static function visibleTo(User $viewer, User $subject): array
    {
        $fields = self::PUBLIC;

        if (self::canViewHrFields($viewer) || $viewer->is($subject)) {
            $fields = array_merge($fields, array_diff(self::HR_ONLY, ['totp_enabled']));
        }

        if ($viewer->can(AccessMatrix::ADMIN_SYSTEM_VIEW) || $viewer->can(AccessMatrix::HR_ACCESS)) {
            $fields[] = 'totp_enabled';
        }

        return array_values(array_unique($fields));
    }

    /**
     * @return list<string>
     */
    public static function editableBy(User $viewer, User $subject): array
    {
        if (! $viewer->is($subject)) {
            return [];
        }

        $fields = array_values(array_diff(self::OWN_EDITABLE, self::OWN_HR_EDITABLE));

        // Emergency contact is on the own-profile form for everyone; HR-only
        // fields stay hidden from other people's profiles.
        return array_merge($fields, self::OWN_HR_EDITABLE);
    }
}
