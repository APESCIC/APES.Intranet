<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Learning\GradeReleaseState;
use Modules\Learning\Models\GradeMark;
use Modules\Learning\Notifications\GradeResultReadyNotification;
use RuntimeException;

class GradeReleaseService
{
    public function __construct(
        private readonly LearningAssignmentService $assignments,
        private readonly AuditLogger $audit,
    ) {}

    public function mark(GradeMark $mark, User $actor): GradeMark
    {
        $this->assertCanEdit($actor, $mark);
        $this->assertTransition($mark, GradeReleaseState::Draft, GradeReleaseState::Marked);

        $mark->update([
            'release_state' => GradeReleaseState::Marked,
            'marked_by' => $actor->id,
            'marked_at' => now(),
        ]);

        $this->audit->record('learning.grade.marked', $mark, [
            'grade_item_id' => $mark->grade_item_id,
        ], $actor);

        return $mark->refresh();
    }

    public function moderate(GradeMark $mark, User $actor): GradeMark
    {
        $this->assertCanEdit($actor, $mark);
        $this->assertTransition($mark, GradeReleaseState::Marked, GradeReleaseState::Moderated);

        $mark->update([
            'release_state' => GradeReleaseState::Moderated,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
        ]);

        $this->audit->record('learning.grade.moderated', $mark, [
            'grade_item_id' => $mark->grade_item_id,
        ], $actor);

        return $mark->refresh();
    }

    public function release(GradeMark $mark, User $actor, bool $notify = true): GradeMark
    {
        $courseId = $mark->gradeItem->course_id;
        if (! $this->assignments->canReleaseGrades($actor, $courseId)) {
            throw new RuntimeException('You need a scoped grade approver role to release grades.');
        }

        $state = $mark->release_state ?? GradeReleaseState::Draft;

        if ($state === GradeReleaseState::Released) {
            return $mark;
        }

        if (! in_array($state, [GradeReleaseState::Marked, GradeReleaseState::Moderated], true)) {
            throw new RuntimeException('Only marked or moderated grades can be released.');
        }

        $mark->update([
            'release_state' => GradeReleaseState::Released,
            'released_by' => $actor->id,
            'released_at' => now(),
        ]);

        $this->audit->record('learning.grade.released', $mark, [
            'grade_item_id' => $mark->grade_item_id,
            'learner_id' => $mark->user_id,
        ], $actor);

        if ($notify) {
            $item = $mark->gradeItem()->with('course')->firstOrFail();
            Notification::send(
                $mark->user,
                new GradeResultReadyNotification($item, $item->course->title),
            );
        }

        return $mark->refresh();
    }

    /**
     * Learner-visible payload: null unless released. Never leaks unreleased scores.
     *
     * @return array{raw_score: float|null, passed: bool|null, released_at: string}|null
     */
    public function visibleToLearner(GradeMark $mark, User $viewer): ?array
    {
        if ($mark->user_id !== $viewer->id) {
            return null;
        }

        if (! $this->isReleased($mark)) {
            return null;
        }

        return [
            'raw_score' => $mark->raw_score !== null ? (float) $mark->raw_score : null,
            'passed' => $mark->passed,
            'released_at' => $mark->released_at?->toIso8601String() ?? '',
        ];
    }

    public function isReleased(GradeMark $mark): bool
    {
        return ($mark->release_state ?? GradeReleaseState::Draft) === GradeReleaseState::Released;
    }

    private function assertCanEdit(User $actor, GradeMark $mark): void
    {
        if (! $this->assignments->canEditGrades($actor, $mark->gradeItem->course_id)) {
            throw new RuntimeException('You need a scoped learning role that can edit grades.');
        }
    }

    private function assertTransition(GradeMark $mark, GradeReleaseState $from, GradeReleaseState $to): void
    {
        $state = $mark->release_state ?? GradeReleaseState::Draft;

        if ($state !== $from) {
            throw new RuntimeException("Cannot move to {$to->value} from {$state->value}.");
        }
    }
}
