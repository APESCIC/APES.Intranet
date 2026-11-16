<?php

namespace Modules\Learning\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\AssignmentFeedback;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use RuntimeException;

class AssignmentSubmissionService
{
    public const DISK = 'local';

    public function __construct(
        private readonly EnrolmentService $enrolments,
        private readonly LearningAssignmentService $assignments,
        private readonly HtmlSanitizer $sanitizer,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{
     *   title: string,
     *   instructions?: ?string,
     *   allow_text?: bool,
     *   allow_file?: bool,
     *   allowed_extensions?: list<string>|null,
     *   max_file_bytes?: ?int,
     *   lesson_id?: ?int,
     *   due_at?: ?string
     * }  $data
     */
    public function createAssignment(Course $course, array $data, User $author): Assignment
    {
        $allowText = $data['allow_text'] ?? true;
        $allowFile = $data['allow_file'] ?? true;
        if (! $allowText && ! $allowFile) {
            throw new InvalidArgumentException('An assignment must allow text, file, or both.');
        }

        $assignment = Assignment::query()->create([
            'course_id' => $course->id,
            'lesson_id' => $data['lesson_id'] ?? null,
            'title' => trim($data['title']),
            'instructions' => $this->sanitizer->clean($data['instructions'] ?? null),
            'allow_text' => (bool) $allowText,
            'allow_file' => (bool) $allowFile,
            'allowed_extensions' => $this->normaliseExtensions($data['allowed_extensions'] ?? null),
            'max_file_bytes' => $data['max_file_bytes'] ?? null,
            'status' => Assignment::STATUS_DRAFT,
            'due_at' => $data['due_at'] ?? null,
            'created_by' => $author->id,
        ]);

        $this->audit->record('learning.assignment.created', $assignment, [
            'course_id' => $course->id,
        ], $author);

        return $assignment;
    }

    public function publishAssignment(Assignment $assignment, User $actor): Assignment
    {
        $assignment->update(['status' => Assignment::STATUS_PUBLISHED]);
        $this->audit->record('learning.assignment.published', $assignment, [
            'course_id' => $assignment->course_id,
        ], $actor);

        return $assignment->refresh();
    }

    public function submit(
        Assignment $assignment,
        User $learner,
        ?string $textBody = null,
        ?UploadedFile $file = null,
    ): AssignmentSubmission {
        if (! $assignment->isPublished()) {
            throw new RuntimeException('Only published assignments accept submissions.');
        }

        if (! $this->enrolments->canAccess($learner, $assignment->course)) {
            throw new RuntimeException('You are not enrolled on this course.');
        }

        $cleanText = $textBody !== null ? $this->sanitizer->clean($textBody) : null;
        $hasText = filled($cleanText);
        $hasFile = $file !== null;

        if (! $hasText && ! $hasFile) {
            throw new InvalidArgumentException('Submit text, a file, or both.');
        }

        if ($hasText && ! $assignment->allow_text) {
            throw new InvalidArgumentException('Text submissions are not allowed for this assignment.');
        }

        if ($hasFile && ! $assignment->allow_file) {
            throw new InvalidArgumentException('File submissions are not allowed for this assignment.');
        }

        $storedPath = null;
        $original = null;
        $mime = null;
        $size = null;

        if ($hasFile) {
            $this->assertAllowedFile($assignment, $file);
            $version = $this->nextVersion($assignment, $learner);
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $dir = sprintf('learning/submissions/%d/%d', $assignment->id, $learner->id);
            $storedPath = $file->storeAs($dir, sprintf('v%d.%s', $version, $ext), self::DISK);
            $original = $file->getClientOriginalName();
            $mime = $file->getClientMimeType();
            $size = $file->getSize() ?: null;
        } else {
            $version = $this->nextVersion($assignment, $learner);
        }

        $submission = AssignmentSubmission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $learner->id,
            'version_number' => $version,
            'text_body' => $hasText ? $cleanText : null,
            'original_filename' => $original,
            'stored_path' => $storedPath,
            'mime' => $mime,
            'size_bytes' => $size,
            'submitted_at' => now(),
        ]);

        $this->audit->record('learning.assignment.submitted', $submission, [
            'assignment_id' => $assignment->id,
            'version' => $version,
            'has_file' => $storedPath !== null,
        ], $learner);

        return $submission;
    }

    /**
     * @return Collection<int, AssignmentSubmission>
     */
    public function versions(Assignment $assignment, User $learner): Collection
    {
        return $assignment->submissions()
            ->where('user_id', $learner->id)
            ->orderBy('version_number')
            ->get();
    }

    public function writeFeedback(
        Assignment $assignment,
        User $learner,
        string $body,
        User $author,
        ?AssignmentSubmission $submission = null,
        bool $release = false,
    ): AssignmentFeedback {
        if (! $this->assignments->canEditGrades($author, $assignment->course_id)) {
            throw new RuntimeException('You do not have a scoped learning role that can write feedback.');
        }

        $feedback = AssignmentFeedback::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $learner->id,
            ],
            [
                'submission_id' => $submission?->id,
                'body' => $this->sanitizer->clean($body),
                'author_id' => $author->id,
                'released_at' => $release ? now() : null,
            ],
        );

        $this->audit->record('learning.assignment.feedback.saved', $feedback, [
            'assignment_id' => $assignment->id,
            'learner_id' => $learner->id,
            'released' => $feedback->isReleased(),
        ], $author);

        return $feedback->refresh();
    }

    /**
     * Release hook for #180 — learners may see feedback only after this.
     */
    public function releaseFeedback(AssignmentFeedback $feedback, User $actor): AssignmentFeedback
    {
        if (! $this->assignments->canReleaseGrades($actor, $feedback->assignment->course_id)) {
            throw new RuntimeException('You do not have a scoped learning role that can release feedback.');
        }

        if (! $feedback->isReleased()) {
            $feedback->update(['released_at' => now()]);
            $this->audit->record('learning.assignment.feedback.released', $feedback, [
                'assignment_id' => $feedback->assignment_id,
                'learner_id' => $feedback->user_id,
            ], $actor);
        }

        return $feedback->refresh();
    }

    /**
     * Feedback the learner is allowed to see (released only). Staff use draft rows directly.
     */
    public function feedbackForLearner(Assignment $assignment, User $learner): ?AssignmentFeedback
    {
        $feedback = AssignmentFeedback::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $learner->id)
            ->first();

        if ($feedback === null || ! $feedback->isReleased()) {
            return null;
        }

        return $feedback;
    }

    private function nextVersion(Assignment $assignment, User $learner): int
    {
        return (int) $assignment->submissions()
            ->where('user_id', $learner->id)
            ->max('version_number') + 1;
    }

    private function assertAllowedFile(Assignment $assignment, UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $allowed = $assignment->allowed_extensions ?? [];

        if ($allowed !== [] && ($ext === '' || ! in_array($ext, $allowed, true))) {
            throw new InvalidArgumentException(
                'That file type is not allowed. Allowed: '.implode(', ', $allowed).'.'
            );
        }

        if ($assignment->max_file_bytes !== null && $file->getSize() > $assignment->max_file_bytes) {
            throw new InvalidArgumentException(
                'That file is too large. Maximum is '.$assignment->max_file_bytes.' bytes.'
            );
        }
    }

    /**
     * @param  list<string>|null  $extensions
     * @return list<string>|null
     */
    private function normaliseExtensions(?array $extensions): ?array
    {
        if ($extensions === null) {
            return null;
        }

        $clean = array_values(array_unique(array_filter(array_map(
            static fn ($e) => strtolower(ltrim(trim((string) $e), '.')),
            $extensions,
        ))));

        return $clean === [] ? null : $clean;
    }
}
