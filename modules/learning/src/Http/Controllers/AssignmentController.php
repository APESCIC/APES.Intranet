<?php

namespace Modules\Learning\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Learning\Models\Assignment;
use Modules\Learning\Models\Course;
use Modules\Learning\Services\AssignmentSubmissionService;
use Modules\Learning\Services\EnrolmentService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssignmentController extends Controller
{
    public function __construct(
        private readonly AssignmentSubmissionService $assignments,
        private readonly EnrolmentService $enrolments,
    ) {}

    public function index(Course $course): View
    {
        Gate::authorize('update', $course);

        return view('learning::courses.assignments.index', [
            'course' => $course->load('courseAssignments'),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'allow_text' => ['nullable', 'boolean'],
            'allow_file' => ['nullable', 'boolean'],
            'allowed_extensions' => ['nullable', 'string'],
            'max_file_bytes' => ['nullable', 'integer', 'min:1'],
        ]);

        $ext = null;
        if (! empty($data['allowed_extensions'])) {
            $ext = array_map('trim', explode(',', $data['allowed_extensions']));
        }

        try {
            $this->assignments->createAssignment($course, [
                'title' => $data['title'],
                'instructions' => $data['instructions'] ?? null,
                'allow_text' => $request->boolean('allow_text', true),
                'allow_file' => $request->boolean('allow_file', true),
                'allowed_extensions' => $ext,
                'max_file_bytes' => $data['max_file_bytes'] ?? null,
            ], $request->user());
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['assignment' => $e->getMessage()]);
        }

        return back()->with('status', 'Assignment created.');
    }

    public function publish(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($assignment->course_id === $course->id, 404);

        $this->assignments->publishAssignment($assignment, $request->user());

        return back()->with('status', 'Assignment published.');
    }

    public function show(Course $course, Assignment $assignment): View
    {
        abort_unless($assignment->course_id === $course->id, 404);
        abort_unless($assignment->isPublished(), 404);

        $user = request()->user();
        abort_unless($this->enrolments->canAccess($user, $course), 403);

        return view('learning::play.assignment', [
            'course' => $course,
            'assignment' => $assignment,
            'versions' => $this->assignments->versions($assignment, $user),
            'feedback' => $this->assignments->feedbackForLearner($assignment, $user),
        ]);
    }

    public function submit(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        abort_unless($assignment->course_id === $course->id, 404);

        $request->validate([
            'text_body' => ['nullable', 'string'],
            'file' => ['nullable', 'file'],
        ]);

        try {
            $this->assignments->submit(
                $assignment,
                $request->user(),
                $request->input('text_body'),
                $request->file('file'),
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['submission' => $e->getMessage()]);
        }

        return back()->with('status', 'Submission saved (new version kept).');
    }

    public function download(Course $course, Assignment $assignment, int $submission): StreamedResponse
    {
        abort_unless($assignment->course_id === $course->id, 404);
        $row = $assignment->submissions()->whereKey($submission)->firstOrFail();
        $user = request()->user();

        $isOwner = $row->user_id === $user->id;
        $canStaff = Gate::allows('learning.grade.view', $course->id);
        abort_unless($isOwner || $canStaff, 403);
        abort_unless($row->hasFile(), 404);

        return response()->streamDownload(function () use ($row): void {
            echo Storage::disk(AssignmentSubmissionService::DISK)
                ->get($row->stored_path);
        }, $row->original_filename ?: basename((string) $row->stored_path));
    }

    public function storeFeedback(Request $request, Course $course, Assignment $assignment): RedirectResponse
    {
        Gate::authorize('learning.grade.edit', $course->id);
        abort_unless($assignment->course_id === $course->id, 404);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'body' => ['required', 'string'],
            'release' => ['nullable', 'boolean'],
        ]);

        $learner = User::query()->findOrFail($data['user_id']);

        try {
            $feedback = $this->assignments->writeFeedback(
                $assignment,
                $learner,
                $data['body'],
                $request->user(),
                null,
                false,
            );
            if ($request->boolean('release')) {
                $this->assignments->releaseFeedback($feedback, $request->user());
            }
        } catch (Throwable $e) {
            return back()->withErrors(['feedback' => $e->getMessage()]);
        }

        return back()->with('status', 'Feedback saved.');
    }
}
