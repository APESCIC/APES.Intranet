<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(LearningPermissions::COURSE_AUTHOR)
            || $user->can(LearningPermissions::ACCESS);
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->can(LearningPermissions::COURSE_AUTHOR)) {
            return true;
        }

        return $user->can(LearningPermissions::ACCESS) && $course->isPublished();
    }

    public function create(User $user): bool
    {
        return $user->can(LearningPermissions::COURSE_AUTHOR);
    }

    public function update(User $user, Course $course): bool
    {
        return $user->can(LearningPermissions::COURSE_AUTHOR);
    }

    public function publish(User $user, Course $course): bool
    {
        return $user->can(LearningPermissions::COURSE_AUTHOR);
    }
}
