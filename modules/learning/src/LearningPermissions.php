<?php

namespace Modules\Learning;

use App\Core\Access\RoleName;

/**
 * Spatie permission names for Learning routes and menus. Grade edit and
 * release are never granted through Cloudron roles: policies require a scoped
 * LearningRoleAssignment (Admin and HR do not get an implicit override).
 */
final class LearningPermissions
{
    public const ACCESS = 'learning.access';

    public const COURSE_AUTHOR = 'learning.course.author';

    public const ENROL_MANAGE = 'learning.enrol.manage';

    public const ASSIGNMENTS_MANAGE = 'learning.assignments.manage';

    public const GRADE_VIEW = 'learning.grade.view';

    public const GRADE_EDIT = 'learning.grade.edit';

    public const GRADE_RELEASE = 'learning.grade.release';

    /**
     * @return array<string, string>
     */
    public static function permissions(): array
    {
        return [
            self::ACCESS => 'Open the Learning area (My learning and courses)',
            self::COURSE_AUTHOR => 'Create and edit course content',
            self::ENROL_MANAGE => 'Manage enrolments and cohorts',
            self::ASSIGNMENTS_MANAGE => 'Assign scoped learning roles to people',
            self::GRADE_VIEW => 'View grades where a scoped learning role allows it',
            self::GRADE_EDIT => 'Edit grades where a scoped learning role allows it (never from Admin/HR alone)',
            self::GRADE_RELEASE => 'Release grades where a scoped learning role allows it (never from Admin/HR alone)',
        ];
    }

    /**
     * Cloudron role grants. Grade edit and release are intentionally omitted
     * for every role, including Admin.
     *
     * @return array<string, list<string>>
     */
    public static function grants(): array
    {
        $member = [self::ACCESS];
        $authoring = [self::ACCESS, self::COURSE_AUTHOR, self::ENROL_MANAGE, self::GRADE_VIEW];

        return [
            RoleName::Staff->value => $member,
            RoleName::Volunteer->value => $member,
            RoleName::Student->value => $member,
            RoleName::Supervisor->value => $member,
            RoleName::Management->value => $authoring,
            RoleName::Hr->value => $member,
            RoleName::Admin->value => [
                self::ACCESS,
                self::COURSE_AUTHOR,
                self::ENROL_MANAGE,
                self::ASSIGNMENTS_MANAGE,
                self::GRADE_VIEW,
                // No GRADE_EDIT or GRADE_RELEASE — needs a LearningRoleAssignment.
            ],
        ];
    }

    /**
     * Permissions that must never be granted through Cloudron roles alone.
     *
     * @return list<string>
     */
    public static function assignmentOnly(): array
    {
        return [self::GRADE_EDIT, self::GRADE_RELEASE];
    }
}
