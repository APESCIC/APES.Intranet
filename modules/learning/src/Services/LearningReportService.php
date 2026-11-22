<?php

namespace Modules\Learning\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\Models\Certificate;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Models\Enrolment;
use Modules\Learning\Models\GradeMark;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningReportService
{
    public function __construct(
        private readonly LearningAssignmentService $assignments,
        private readonly GradeViewExportService $gradeExport,
    ) {}

    public function minGroupSize(): int
    {
        return max(2, (int) config('learning.reports.min_group_size', 5));
    }

    public function assertCanReport(User $actor, Course $course): void
    {
        if (! $this->assignments->canViewOthersGrades($actor, $course->id)) {
            throw new RuntimeException('Learning reports need a scoped role that can view others’ grades.');
        }
    }

    /**
     * @return array{
     *     enrolled: int,
     *     completed: int,
     *     overdue: int,
     *     rows: Collection<int, array{user: User, completed: bool, overdue: bool}>
     * }
     */
    public function completionReport(User $actor, Course $course): array
    {
        $this->assertCanReport($actor, $course);

        $enrolments = Enrolment::query()
            ->with('user')
            ->where('course_id', $course->id)
            ->where('status', Enrolment::STATUS_ACTIVE)
            ->get();

        $completionUserIds = CourseCompletion::query()
            ->where('course_id', $course->id)
            ->pluck('user_id')
            ->all();

        $currentCertUserIds = Certificate::query()
            ->where('course_id', $course->id)
            ->whereNull('revoked_at')
            ->get()
            ->filter(fn (Certificate $c) => $c->isCurrent())
            ->pluck('user_id')
            ->all();

        $satisfied = array_unique([...$completionUserIds, ...$currentCertUserIds]);

        $rows = $enrolments
            ->filter(fn (Enrolment $enrolment) => $enrolment->user instanceof User)
            ->map(function (Enrolment $enrolment) use ($satisfied) {
                /** @var User $user */
                $user = $enrolment->user;
                $completed = in_array($user->id, $satisfied, true);

                return [
                    'user' => $user,
                    'completed' => $completed,
                    'overdue' => ! $completed,
                ];
            })
            ->values();

        return [
            'enrolled' => $rows->count(),
            'completed' => $rows->where('completed', true)->count(),
            'overdue' => $rows->where('overdue', true)->count(),
            'rows' => $rows,
        ];
    }

    /**
     * Grade distribution buckets. Returns suppressed=true when the cohort is
     * too small to show identifiable distributions.
     *
     * @return array{suppressed: bool, reason: ?string, total: int, buckets: list<array{label: string, count: int}>}
     */
    public function gradeDistribution(User $actor, Course $course): array
    {
        $this->assertCanReport($actor, $course);

        $marks = GradeMark::query()
            ->whereHas('gradeItem', fn ($q) => $q->where('course_id', $course->id))
            ->whereNotNull('raw_score')
            ->get();

        $total = $marks->count();
        $min = $this->minGroupSize();

        if ($total < $min) {
            return [
                'suppressed' => true,
                'reason' => "Grade distributions are hidden when fewer than {$min} marks are available, so individuals cannot be identified.",
                'total' => $total,
                'buckets' => [],
            ];
        }

        $buckets = [
            '0–39' => 0,
            '40–59' => 0,
            '60–69' => 0,
            '70–79' => 0,
            '80–100' => 0,
        ];

        foreach ($marks as $mark) {
            $score = (float) $mark->raw_score;
            if ($score < 40) {
                $buckets['0–39']++;
            } elseif ($score < 60) {
                $buckets['40–59']++;
            } elseif ($score < 70) {
                $buckets['60–69']++;
            } elseif ($score < 80) {
                $buckets['70–79']++;
            } else {
                $buckets['80–100']++;
            }
        }

        return [
            'suppressed' => false,
            'reason' => null,
            'total' => $total,
            'buckets' => collect($buckets)->map(fn (int $count, string $label) => [
                'label' => $label,
                'count' => $count,
            ])->values()->all(),
        ];
    }

    public function exportCompletionCsv(User $actor, Course $course): StreamedResponse
    {
        $report = $this->completionReport($actor, $course);
        $filename = 'learning-completions-'.$course->id.'.csv';

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['learner', 'email', 'completed', 'overdue']);

            foreach ($report['rows'] as $row) {
                /** @var User $user */
                $user = $row['user'];
                fputcsv($out, [
                    $this->gradeExport->escapeCsvField($user->name),
                    $this->gradeExport->escapeCsvField($user->email),
                    $row['completed'] ? '1' : '0',
                    $row['overdue'] ? '1' : '0',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Courses the actor may open reports for.
     *
     * @return Collection<int, Course>
     */
    public function reportableCourses(User $actor): Collection
    {
        return Course::query()
            ->orderBy('title')
            ->get()
            ->filter(fn (Course $course) => $this->assignments->canViewOthersGrades($actor, $course->id))
            ->values();
    }
}
