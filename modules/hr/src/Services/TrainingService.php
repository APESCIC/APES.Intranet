<?php

namespace Modules\Hr\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Hr\Models\TrainingCompletion;
use Modules\Hr\Models\TrainingCourse;
use Modules\Hr\Models\TrainingRequirement;

final class TrainingService
{
    public function ensureDefaults(): void
    {
        $lms = rtrim((string) config('hr.lms_base_url', 'https://lms.intranet.apes.org.uk'), '/');

        $defaults = [
            ['code' => 'safeguarding', 'title' => 'Safeguarding essentials', 'path' => '/course/view.php?id=1', 'roles' => ['staff', 'supervisor', 'management', 'hr', 'admin']],
            ['code' => 'h-and-s', 'title' => 'Health and safety induction', 'path' => '/course/view.php?id=2', 'roles' => ['staff', 'volunteer', 'supervisor']],
            ['code' => 'data-protection', 'title' => 'Data protection for staff', 'path' => '/course/view.php?id=3', 'roles' => ['staff', 'hr', 'admin', 'legal']],
        ];

        foreach ($defaults as $row) {
            $course = TrainingCourse::query()->firstOrCreate(
                ['code' => $row['code']],
                ['title' => $row['title'], 'lms_url' => $lms.$row['path'], 'active' => true],
            );

            foreach ($row['roles'] as $role) {
                TrainingRequirement::query()->firstOrCreate(
                    ['course_id' => $course->id, 'role' => $role],
                    ['renew_months' => 24],
                );
            }
        }
    }

    /**
     * Required courses for a member with latest completion status.
     *
     * @return Collection<int, array{course: TrainingCourse, completion: ?TrainingCompletion, overdue: bool}>
     */
    public function forMember(User $user): Collection
    {
        $this->ensureDefaults();
        $roles = $user->getRoleNames()->map(fn ($n) => strtolower((string) $n))->all();

        $courseIds = TrainingRequirement::query()
            ->whereIn('role', $roles)
            ->pluck('course_id')
            ->unique();

        return TrainingCourse::query()
            ->whereIn('id', $courseIds)
            ->where('active', true)
            ->orderBy('title')
            ->get()
            ->map(function (TrainingCourse $course) use ($user) {
                $completion = TrainingCompletion::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->orderByDesc('completed_on')
                    ->first();

                $overdue = $completion === null || ! $completion->isCurrent();

                return [
                    'course' => $course,
                    'completion' => $completion,
                    'overdue' => $overdue,
                ];
            });
    }

    /**
     * Members with at least one overdue required course.
     *
     * @return Collection<int, array{user: User, overdue: list<TrainingCourse>}>
     */
    public function overdueReport(): Collection
    {
        $this->ensureDefaults();

        return User::query()
            ->active()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $overdue = $this->forMember($user)
                    ->filter(fn (array $row) => $row['overdue'])
                    ->pluck('course')
                    ->values()
                    ->all();

                return ['user' => $user, 'overdue' => $overdue];
            })
            ->filter(fn (array $row) => $row['overdue'] !== [])
            ->values();
    }

    public function recordCompletion(User $member, TrainingCourse $course, Carbon $completedOn, User $recorder, ?string $notes = null): TrainingCompletion
    {
        $requirement = TrainingRequirement::query()
            ->where('course_id', $course->id)
            ->whereIn('role', $member->getRoleNames()->map(fn ($n) => strtolower((string) $n))->all())
            ->first();

        $expires = null;
        if ($requirement?->renew_months) {
            $expires = $completedOn->copy()->addMonths($requirement->renew_months);
        }

        return TrainingCompletion::query()->create([
            'user_id' => $member->id,
            'course_id' => $course->id,
            'completed_on' => $completedOn->toDateString(),
            'expires_on' => $expires?->toDateString(),
            'recorded_by' => $recorder->id,
            'notes' => $notes,
        ]);
    }
}
