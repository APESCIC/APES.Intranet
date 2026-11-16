<?php

namespace Modules\Learning\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseCompletion;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use RuntimeException;

/**
 * Lesson and course progress/completion. Grades are out of scope: this
 * service never reads or writes grade data.
 */
class ProgressService
{
    public function __construct(
        private readonly EnrolmentService $enrolments,
        private readonly CourseAuthoringService $authoring,
    ) {}

    public function startLesson(User $learner, Lesson $lesson): LessonProgress
    {
        $course = $lesson->section->course;
        $this->assertCanTrack($learner, $course);

        $version = $this->authoring->lockLearnerVersion($learner, $lesson);

        $progress = LessonProgress::query()->firstOrNew([
            'user_id' => $learner->id,
            'lesson_id' => $lesson->id,
        ]);

        if ($progress->started_at === null) {
            $progress->started_at = now();
        }

        $progress->lesson_version_id = $version->id;
        $progress->save();

        return $progress->refresh();
    }

    public function completeLesson(User $learner, Lesson $lesson): LessonProgress
    {
        $progress = $this->startLesson($learner, $lesson);

        if ($progress->completed_at === null) {
            $progress->update(['completed_at' => now()]);
        }

        $this->recomputeCourseCompletion($learner, $lesson->section->course);

        return $progress->refresh();
    }

    /**
     * Course is complete when every published lesson in the course has a
     * completed_at for this learner. No grade threshold is applied.
     */
    public function recomputeCourseCompletion(User $learner, Course $course): ?CourseCompletion
    {
        $lessonIds = $this->publishedLessonIds($course);

        if ($lessonIds->isEmpty()) {
            return null;
        }

        $completedCount = LessonProgress::query()
            ->where('user_id', $learner->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        if ($completedCount < $lessonIds->count()) {
            CourseCompletion::query()
                ->where('user_id', $learner->id)
                ->where('course_id', $course->id)
                ->delete();

            return null;
        }

        return CourseCompletion::query()->updateOrCreate(
            [
                'user_id' => $learner->id,
                'course_id' => $course->id,
            ],
            [
                'completed_at' => now(),
            ],
        );
    }

    public function isLessonComplete(User $learner, Lesson $lesson): bool
    {
        return LessonProgress::query()
            ->where('user_id', $learner->id)
            ->where('lesson_id', $lesson->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    public function isCourseComplete(User $learner, Course $course): bool
    {
        return CourseCompletion::query()
            ->where('user_id', $learner->id)
            ->where('course_id', $course->id)
            ->exists();
    }

    /**
     * @return array{total: int, completed: int, percent: int}
     */
    public function courseProgressSummary(User $learner, Course $course): array
    {
        $lessonIds = $this->publishedLessonIds($course);
        $total = $lessonIds->count();

        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'percent' => 0];
        }

        $completed = LessonProgress::query()
            ->where('user_id', $learner->id)
            ->whereIn('lesson_id', $lessonIds)
            ->whereNotNull('completed_at')
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => (int) floor(($completed / $total) * 100),
        ];
    }

    public function progressForLesson(User $learner, Lesson $lesson): ?LessonProgress
    {
        return LessonProgress::query()
            ->where('user_id', $learner->id)
            ->where('lesson_id', $lesson->id)
            ->first();
    }

    /**
     * @return Collection<int, int>
     */
    private function publishedLessonIds(Course $course): Collection
    {
        $course->loadMissing(['sections.lessons.versions']);

        return $course->sections
            ->flatMap(fn ($section) => $section->lessons)
            ->filter(fn (Lesson $lesson) => $lesson->versions->isNotEmpty())
            ->pluck('id')
            ->values();
    }

    private function assertCanTrack(User $learner, Course $course): void
    {
        if (! $this->enrolments->canAccess($learner, $course)) {
            throw new RuntimeException('Learner cannot track progress on this course.');
        }
    }
}
