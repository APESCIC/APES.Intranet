<?php

namespace Modules\Learning\Services;

use App\Core\Auth\GroupRoleMapper;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\EnrolmentMethod;
use Modules\Learning\Models\Cohort;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Models\CourseGroupRule;
use Modules\Learning\Models\CoursePrerequisite;
use Modules\Learning\Models\Enrolment;
use RuntimeException;

class EnrolmentService
{
    public function __construct(private readonly GroupRoleMapper $groupRoles) {}

    /**
     * @param  array{cohort_id?: ?int, starts_at?: ?string, ends_at?: ?string}  $options
     */
    public function enrolManual(Course $course, User $learner, User $actor, array $options = []): Enrolment
    {
        return $this->upsertEnrolment(
            course: $course,
            learner: $learner,
            method: EnrolmentMethod::Manual,
            sourceGroup: null,
            cohortId: $options['cohort_id'] ?? null,
            startsAt: $options['starts_at'] ?? null,
            endsAt: $options['ends_at'] ?? null,
            enrolledBy: $actor->id,
        );
    }

    /**
     * @param  array{starts_at?: ?string, ends_at?: ?string}  $options
     */
    public function enrolSelf(Course $course, User $learner, array $options = []): Enrolment
    {
        if (! $course->self_enrol_enabled) {
            throw new RuntimeException('Self-enrol is not enabled for this course.');
        }

        if (! $course->isPublished()) {
            throw new RuntimeException('Only published courses accept self-enrolment.');
        }

        if (! $this->prerequisitesMet($learner, $course)) {
            throw new RuntimeException('Prerequisites are not met.');
        }

        return $this->upsertEnrolment(
            course: $course,
            learner: $learner,
            method: EnrolmentMethod::Self,
            sourceGroup: null,
            cohortId: null,
            startsAt: $options['starts_at'] ?? null,
            endsAt: $options['ends_at'] ?? null,
            enrolledBy: $learner->id,
        );
    }

    public function attachGroupRule(Course $course, string $directoryGroup, ?Cohort $cohort = null): CourseGroupRule
    {
        $group = strtolower(trim($directoryGroup));
        if (! in_array($group, $this->groupRoles->approvedGroups(), true)) {
            throw new RuntimeException("Unknown canonical group: {$directoryGroup}");
        }

        return CourseGroupRule::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'directory_group' => $group,
            ],
            [
                'cohort_id' => $cohort?->id,
            ],
        );
    }

    public function addPrerequisite(Course $course, Course $prerequisite): CoursePrerequisite
    {
        if ($course->id === $prerequisite->id) {
            throw new RuntimeException('A course cannot require itself.');
        }

        return CoursePrerequisite::query()->firstOrCreate([
            'course_id' => $course->id,
            'prerequisite_course_id' => $prerequisite->id,
        ]);
    }

    /**
     * Reconcile group-based enrolments for one learner from their Spatie roles
     * (mapped from canonical Cloudron groups at sign-in).
     */
    public function syncGroupEnrolmentsForUser(User $learner): void
    {
        $groups = $this->directoryGroupsForUser($learner);
        $rules = CourseGroupRule::query()->with('course')->get();

        foreach ($rules as $rule) {
            $shouldHave = in_array($rule->directory_group, $groups, true);
            $existing = Enrolment::query()
                ->where('user_id', $learner->id)
                ->where('course_id', $rule->course_id)
                ->first();

            if ($shouldHave) {
                if ($existing !== null && $existing->method !== EnrolmentMethod::Group) {
                    // Manual/self enrolments are not overwritten by group sync.
                    continue;
                }

                $this->upsertEnrolment(
                    course: $rule->course,
                    learner: $learner,
                    method: EnrolmentMethod::Group,
                    sourceGroup: $rule->directory_group,
                    cohortId: $rule->cohort_id,
                    startsAt: null,
                    endsAt: null,
                    enrolledBy: null,
                );
            } elseif ($existing !== null
                && $existing->method === EnrolmentMethod::Group
                && $existing->source_group === $rule->directory_group
                && $existing->isActive()
            ) {
                $this->endEnrolment($existing);
            }
        }

        // End any active group enrolment whose source group the learner no longer has,
        // even if the rule was removed.
        Enrolment::query()
            ->where('user_id', $learner->id)
            ->where('method', EnrolmentMethod::Group->value)
            ->where('status', Enrolment::STATUS_ACTIVE)
            ->whereNotNull('source_group')
            ->get()
            ->each(function (Enrolment $enrolment) use ($groups): void {
                if (! in_array((string) $enrolment->source_group, $groups, true)) {
                    $this->endEnrolment($enrolment);
                }
            });
    }

    /**
     * Sync every user who holds a Spatie role matching a course group rule.
     */
    public function syncGroupEnrolmentsForCourse(Course $course): void
    {
        $rules = $course->groupRules()->get();
        if ($rules->isEmpty()) {
            return;
        }

        /** @var array<string, string> $groupToRole */
        $groupToRole = config('intranet.group_roles', []);
        $roles = [];
        foreach ($rules as $rule) {
            $role = $groupToRole[$rule->directory_group] ?? null;
            if ($role !== null) {
                $roles[] = $role;
            }
        }

        if ($roles === []) {
            return;
        }

        User::role(array_values(array_unique($roles)))
            ->each(fn (User $user) => $this->syncGroupEnrolmentsForUser($user));
    }

    public function endEnrolment(Enrolment $enrolment): Enrolment
    {
        $enrolment->update([
            'status' => Enrolment::STATUS_ENDED,
            'ended_at' => now(),
        ]);

        return $enrolment->refresh();
    }

    public function markCourseComplete(User $learner, Course $course): CourseCompletion
    {
        $completion = CourseCompletion::query()->updateOrCreate(
            [
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ],
            [
                'completed_at' => now(),
            ],
        );

        app(CertificateService::class)->issueForCompletion($learner, $course, $completion);

        return $completion;
    }

    public function prerequisitesMet(User $learner, Course $course): bool
    {
        $required = $course->prerequisites()->pluck('prerequisite_course_id');
        if ($required->isEmpty()) {
            return true;
        }

        $completed = CourseCompletion::query()
            ->where('user_id', $learner->id)
            ->whereIn('course_id', $required)
            ->pluck('course_id');

        return $required->diff($completed)->isEmpty();
    }

    /**
     * Whether the learner may open the course player: active enrolment within
     * dates, and every prerequisite completed.
     */
    public function canAccess(User $learner, Course $course): bool
    {
        $enrolment = Enrolment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->where('status', Enrolment::STATUS_ACTIVE)
            ->first();

        if ($enrolment === null || ! $enrolment->isWithinDates()) {
            return false;
        }

        return $this->prerequisitesMet($learner, $course);
    }

    public function activeEnrolment(User $learner, Course $course): ?Enrolment
    {
        return Enrolment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->where('status', Enrolment::STATUS_ACTIVE)
            ->first();
    }

    /**
     * Canonical directory groups inferred from the learner's Spatie roles
     * (roles are set from Cloudron groups at OIDC sign-in).
     *
     * @return list<string>
     */
    public function directoryGroupsForUser(User $learner): array
    {
        /** @var array<string, string> $map */
        $map = config('intranet.group_roles', []);
        $roleToGroup = array_flip($map);
        $groups = [];

        foreach ($learner->getRoleNames() as $role) {
            $group = $roleToGroup[$role] ?? null;
            if ($group !== null) {
                $groups[] = $group;
            }
        }

        return array_values(array_unique($groups));
    }

    /**
     * @return Collection<int, Enrolment>
     */
    public function enrolmentsForCourse(Course $course): Collection
    {
        return $course->enrolments()->with(['user', 'cohort'])->orderByDesc('enrolled_at')->get();
    }

    private function upsertEnrolment(
        Course $course,
        User $learner,
        EnrolmentMethod $method,
        ?string $sourceGroup,
        ?int $cohortId,
        mixed $startsAt,
        mixed $endsAt,
        ?int $enrolledBy,
    ): Enrolment {
        $existing = Enrolment::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->first();

        $payload = [
            'cohort_id' => $cohortId,
            'method' => $method->value,
            'status' => Enrolment::STATUS_ACTIVE,
            'source_group' => $sourceGroup,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'ended_at' => null,
            'enrolled_by' => $enrolledBy,
        ];

        if ($existing === null) {
            $payload['enrolled_at'] = now();

            return Enrolment::query()->create([
                'user_id' => $learner->id,
                'course_id' => $course->id,
                ...$payload,
            ]);
        }

        // enrolled_at is non-nullable and set on create; keep the original stamp on reactivate.
        $existing->update($payload);

        return $existing->refresh();
    }
}
