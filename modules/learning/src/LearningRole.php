<?php

namespace Modules\Learning;

/**
 * Learning-owned roles, assigned per person with an optional course or cohort
 * scope. These are not Cloudron / Spatie intranet roles.
 */
enum LearningRole: string
{
    case Learner = 'learner';
    case Instructor = 'instructor';
    case Assessor = 'assessor';
    case GradeApprover = 'grade_approver';
    case LearningAdmin = 'learning_admin';

    public function label(): string
    {
        return match ($this) {
            self::Learner => 'Learner',
            self::Instructor => 'Instructor',
            self::Assessor => 'Assessor',
            self::GradeApprover => 'Grade approver',
            self::LearningAdmin => 'Learning admin',
        };
    }

    /**
     * Roles that may edit grades within their assignment scope.
     *
     * @return list<self>
     */
    public static function canEditGrades(): array
    {
        return [self::Assessor, self::GradeApprover, self::LearningAdmin];
    }

    /**
     * Roles that may release grades within their assignment scope.
     *
     * @return list<self>
     */
    public static function canReleaseGrades(): array
    {
        return [self::GradeApprover, self::LearningAdmin];
    }

    /**
     * Roles that may view grades for others within scope (not only their own).
     *
     * @return list<self>
     */
    public static function canViewOthersGrades(): array
    {
        return [self::Instructor, self::Assessor, self::GradeApprover, self::LearningAdmin];
    }
}
