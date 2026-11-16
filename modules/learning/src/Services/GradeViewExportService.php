<?php

namespace Modules\Learning\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\GradeReleaseState;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\GradeItem;
use Modules\Learning\Models\GradeMark;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeViewExportService
{
    public function __construct(
        private readonly LearningAssignmentService $assignments,
        private readonly GradeReleaseService $release,
    ) {}

    /**
     * Released marks for the learner across a course (or all learning courses).
     *
     * @return Collection<int, array{course: string, item: string, score: float|null, passed: bool|null, released_at: string}>
     */
    public function learnerReleasedGrades(User $learner, ?Course $course = null): Collection
    {
        $query = GradeMark::query()
            ->with(['gradeItem.course'])
            ->where('user_id', $learner->id)
            ->where('release_state', GradeReleaseState::Released->value);

        if ($course !== null) {
            $query->whereHas('gradeItem', fn ($q) => $q->where('course_id', $course->id));
        }

        return $query->orderBy('id')->get()->map(function (GradeMark $mark) use ($learner) {
            $visible = $this->release->visibleToLearner($mark, $learner);
            if ($visible === null) {
                return null;
            }

            return [
                'course' => $mark->gradeItem->course->title,
                'item' => $mark->gradeItem->title,
                'score' => $visible['raw_score'],
                'passed' => $visible['passed'],
                'released_at' => $visible['released_at'],
            ];
        })->filter()->values();
    }

    /**
     * Scoped CSV for instructors/approvers: only courses they can view others' grades on.
     */
    public function exportCsv(User $actor, Course $course): StreamedResponse
    {
        if (! $this->assignments->canViewOthersGrades($actor, $course->id)) {
            throw new RuntimeException('CSV export needs a scoped learning role that can view others’ grades.');
        }

        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->with(['marks.user'])
            ->orderBy('id')
            ->get();

        $filename = 'learning-grades-'.$course->id.'.csv';

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['learner', 'email', 'grade_item', 'score', 'passed', 'release_state']);

            foreach ($items as $item) {
                foreach ($item->marks as $mark) {
                    fputcsv($out, [
                        $this->escapeCsvField($mark->user->name),
                        $this->escapeCsvField($mark->user->email),
                        $this->escapeCsvField($item->title),
                        $this->escapeCsvField($mark->raw_score !== null ? (string) $mark->raw_score : ''),
                        $this->escapeCsvField($mark->passed === null ? '' : ($mark->passed ? '1' : '0')),
                        $this->escapeCsvField($mark->release_state->value),
                    ]);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Neutralise spreadsheet formula injection for CSV cells.
     */
    public function escapeCsvField(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        $first = $value[0];
        if (in_array($first, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
