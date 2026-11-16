<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Learning\Models\GradeAppeal;
use Modules\Learning\Models\GradeEvent;
use Modules\Learning\Models\GradeItem;
use Modules\Learning\Models\GradeMark;
use RuntimeException;

class GradeAuditService
{
    public function __construct(
        private readonly LearningAssignmentService $assignments,
        private readonly HtmlSanitizer $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return Collection<int, GradeEvent>
     */
    public function history(GradeItem $item, User $learner): Collection
    {
        return GradeEvent::query()
            ->where('grade_item_id', $item->id)
            ->where('user_id', $learner->id)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();
    }

    public function override(
        GradeItem $item,
        User $learner,
        float $newScore,
        string $reason,
        User $actor,
    ): GradeMark {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('An override needs a reason.');
        }

        if (! $this->assignments->canEditGrades($actor, $item->course_id)) {
            throw new RuntimeException('Overrides need a scoped learning role that can edit grades (Admin alone is not enough).');
        }

        $mark = GradeMark::query()->firstOrNew([
            'grade_item_id' => $item->id,
            'user_id' => $learner->id,
        ]);

        $old = $mark->exists ? ($mark->raw_score !== null ? (float) $mark->raw_score : null) : null;

        $mark->fill([
            'raw_score' => $newScore,
            'marked_by' => $actor->id,
            'marked_at' => now(),
        ])->save();

        $this->appendEvent($item, $learner, $actor, 'override', $old, $newScore, $reason);

        return $mark->refresh();
    }

    public function openAppeal(GradeItem $item, User $learner, string $grounds): GradeAppeal
    {
        if (trim($grounds) === '') {
            throw new InvalidArgumentException('An appeal needs grounds.');
        }

        $open = GradeAppeal::query()
            ->where('grade_item_id', $item->id)
            ->where('user_id', $learner->id)
            ->where('status', GradeAppeal::STATUS_OPEN)
            ->exists();

        if ($open) {
            throw new RuntimeException('An open appeal already exists for this grade.');
        }

        $appeal = GradeAppeal::query()->create([
            'grade_item_id' => $item->id,
            'user_id' => $learner->id,
            'grounds' => $this->sanitizer->clean($grounds),
            'status' => GradeAppeal::STATUS_OPEN,
        ]);

        $mark = GradeMark::query()
            ->where('grade_item_id', $item->id)
            ->where('user_id', $learner->id)
            ->first();

        $score = $mark?->raw_score !== null ? (float) $mark->raw_score : null;
        $this->appendEvent($item, $learner, $learner, 'appeal_opened', $score, $score, 'Appeal opened: '.$grounds);

        return $appeal;
    }

    public function resolveAppeal(
        GradeAppeal $appeal,
        User $actor,
        string $outcome,
        string $notes,
        ?float $newScore = null,
    ): GradeAppeal {
        if (! $appeal->isOpen()) {
            throw new RuntimeException('This appeal is already resolved.');
        }

        if (! in_array($outcome, [GradeAppeal::STATUS_UPHELD, GradeAppeal::STATUS_REJECTED], true)) {
            throw new InvalidArgumentException('Outcome must be upheld or rejected.');
        }

        if (! $this->assignments->canEditGrades($actor, $appeal->gradeItem->course_id)) {
            throw new RuntimeException('Resolving appeals needs a scoped learning role that can edit grades.');
        }

        $mark = GradeMark::query()
            ->where('grade_item_id', $appeal->grade_item_id)
            ->where('user_id', $appeal->user_id)
            ->first();

        $old = $mark?->raw_score !== null ? (float) $mark->raw_score : null;
        $finalScore = $old;

        if ($outcome === GradeAppeal::STATUS_UPHELD && $newScore !== null) {
            if ($mark === null) {
                $mark = new GradeMark([
                    'grade_item_id' => $appeal->grade_item_id,
                    'user_id' => $appeal->user_id,
                ]);
            }
            $mark->fill([
                'raw_score' => $newScore,
                'marked_by' => $actor->id,
                'marked_at' => now(),
            ])->save();
            $finalScore = $newScore;
        }

        $appeal->update([
            'status' => $outcome,
            'outcome_notes' => $this->sanitizer->clean($notes),
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
        ]);

        $this->appendEvent(
            $appeal->gradeItem,
            $appeal->learner,
            $actor,
            'appeal_resolved',
            $old,
            $finalScore,
            'Appeal '.$outcome.': '.$notes,
        );

        return $appeal->refresh();
    }

    private function appendEvent(
        GradeItem $item,
        User $learner,
        User $actor,
        string $type,
        ?float $old,
        ?float $new,
        string $reason,
    ): GradeEvent {
        $event = GradeEvent::query()->create([
            'grade_item_id' => $item->id,
            'user_id' => $learner->id,
            'actor_id' => $actor->id,
            'event_type' => $type,
            'old_score' => $old,
            'new_score' => $new,
            'reason' => trim($reason),
            'occurred_at' => now(),
        ]);

        $this->audit->record('learning.grade.event', $event, [
            'event_type' => $type,
            'grade_item_id' => $item->id,
            'learner_id' => $learner->id,
            'old_score' => $old,
            'new_score' => $new,
        ], $actor);

        return $event;
    }
}
