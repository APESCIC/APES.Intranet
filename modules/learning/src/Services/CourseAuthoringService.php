<?php

namespace Modules\Learning\Services;

use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LearnerVersionLock;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonVersion;
use Modules\Learning\Models\Section;
use RuntimeException;

class CourseAuthoringService
{
    public function __construct(private readonly HtmlSanitizer $sanitizer) {}

    /**
     * @param  array{title: string, summary?: ?string, slug?: ?string}  $data
     */
    public function createCourse(array $data, User $author): Course
    {
        $slug = $this->uniqueSlug($data['slug'] ?? $data['title']);

        return Course::query()->create([
            'slug' => $slug,
            'title' => $data['title'],
            'summary' => $data['summary'] ?? null,
            'status' => Course::STATUS_DRAFT,
            'created_by' => $author->id,
        ]);
    }

    public function addSection(Course $course, string $title): Section
    {
        return $course->sections()->create([
            'title' => $title,
            'sort_order' => (int) $course->sections()->max('sort_order') + 1,
        ]);
    }

    public function addLesson(Section $section, string $title, ?string $draftBody = null): Lesson
    {
        return $section->lessons()->create([
            'title' => $title,
            'draft_body' => $this->sanitizer->clean($draftBody),
            'sort_order' => (int) $section->lessons()->max('sort_order') + 1,
        ]);
    }

    public function updateLessonDraft(Lesson $lesson, string $title, ?string $draftBody): Lesson
    {
        $lesson->update([
            'title' => $title,
            'draft_body' => $this->sanitizer->clean($draftBody),
        ]);

        return $lesson->refresh();
    }

    /**
     * Publishing an edit creates a new lesson version from the sanitised draft.
     * Learners already locked to an older version keep that version.
     */
    public function publishLesson(Lesson $lesson, User $publisher): LessonVersion
    {
        $body = $this->sanitizer->clean($lesson->draft_body);

        if ($body === '') {
            throw new RuntimeException('Cannot publish an empty lesson body.');
        }

        return DB::transaction(function () use ($lesson, $publisher, $body): LessonVersion {
            $next = (int) $lesson->versions()->max('version_number') + 1;

            $version = $lesson->versions()->create([
                'version_number' => $next,
                'body' => $body,
                'published_by' => $publisher->id,
                'published_at' => now(),
            ]);

            $course = $lesson->section->course;
            if ($course->status !== Course::STATUS_PUBLISHED) {
                $course->update(['status' => Course::STATUS_PUBLISHED]);
            }

            return $version;
        });
    }

    /**
     * Lock a learner to the lesson's current published version the first time
     * they start it. Later publishes do not move them.
     */
    public function lockLearnerVersion(User $learner, Lesson $lesson): LessonVersion
    {
        $current = $lesson->currentVersion;
        if ($current === null) {
            throw new RuntimeException('Lesson has no published version to lock.');
        }

        $lock = LearnerVersionLock::query()->firstOrCreate(
            [
                'user_id' => $learner->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'lesson_version_id' => $current->id,
                'locked_at' => now(),
            ],
        );

        return $lock->version;
    }

    /**
     * Version a learner should see: their lock if present, otherwise the latest publish.
     */
    public function versionForLearner(User $learner, Lesson $lesson): ?LessonVersion
    {
        $lock = LearnerVersionLock::query()
            ->where('user_id', $learner->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($lock !== null) {
            return $lock->version;
        }

        return $lesson->currentVersion;
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'course';
        $slug = $base;
        $i = 2;

        while (Course::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
