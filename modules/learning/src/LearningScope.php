<?php

namespace Modules\Learning;

/**
 * How wide a learning role assignment applies. Course and cohort ids are
 * stored as integers until those tables land in later issues; learning_admin
 * uses global scope with a null id.
 */
enum LearningScope: string
{
    case Global = 'global';
    case Course = 'course';
    case Cohort = 'cohort';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'All learning',
            self::Course => 'Course',
            self::Cohort => 'Cohort',
        };
    }
}
