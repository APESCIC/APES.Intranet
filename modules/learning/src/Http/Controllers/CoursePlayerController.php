<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\ProgressService;
use RuntimeException;

class CoursePlayerController extends Controller
{
    public function show(
        Request $request,
        Course $course,
        EnrolmentService $enrolments,
        ProgressService $progress,
    ): View {
        abort_unless($enrolments->canAccess($request->user(), $course), 403);
        abort_unless($course->isPublished(), 404);

        $course->load(['sections.lessons.versions']);

        return view('learning::play.course', [
            'course' => $course,
            'summary' => $progress->courseProgressSummary($request->user(), $course),
            'complete' => $progress->isCourseComplete($request->user(), $course),
            'lessonStates' => $course->sections
                ->flatMap(fn ($section) => $section->lessons)
                ->mapWithKeys(function (Lesson $lesson) use ($request, $progress) {
                    $row = $progress->progressForLesson($request->user(), $lesson);

                    return [$lesson->id => [
                        'started' => $row?->isStarted() ?? false,
                        'completed' => $row?->isCompleted() ?? false,
                    ]];
                }),
        ]);
    }

    public function lesson(
        Request $request,
        Course $course,
        Lesson $lesson,
        EnrolmentService $enrolments,
        ProgressService $progress,
        CourseAuthoringService $authoring,
    ): View|RedirectResponse {
        abort_unless($lesson->section->course_id === $course->id, 404);
        abort_unless($enrolments->canAccess($request->user(), $course), 403);

        try {
            $progress->startLesson($request->user(), $lesson);
            $version = $authoring->versionForLearner($request->user(), $lesson);
        } catch (RuntimeException $e) {
            return redirect()->route('learning.play.show', $course)
                ->withErrors(['lesson' => $e->getMessage()]);
        }

        abort_if($version === null, 404);

        $course->load(['sections.lessons']);

        return view('learning::play.lesson', [
            'course' => $course,
            'lesson' => $lesson,
            'version' => $version,
            'progress' => $progress->progressForLesson($request->user(), $lesson),
            'summary' => $progress->courseProgressSummary($request->user(), $course),
        ]);
    }

    public function complete(
        Request $request,
        Course $course,
        Lesson $lesson,
        EnrolmentService $enrolments,
        ProgressService $progress,
    ): RedirectResponse {
        abort_unless($lesson->section->course_id === $course->id, 404);
        abort_unless($enrolments->canAccess($request->user(), $course), 403);

        try {
            $progress->completeLesson($request->user(), $lesson);
        } catch (RuntimeException $e) {
            return back()->withErrors(['lesson' => $e->getMessage()]);
        }

        $next = $this->nextLesson($course->load(['sections.lessons']), $lesson);

        if ($next !== null) {
            return redirect()
                ->route('learning.play.lesson', [$course, $next])
                ->with('status', 'Lesson marked complete.');
        }

        return redirect()
            ->route('learning.play.show', $course)
            ->with('status', 'Lesson marked complete.');
    }

    private function nextLesson(Course $course, Lesson $current): ?Lesson
    {
        $flat = $course->sections->flatMap(fn ($section) => $section->lessons)->values();
        $index = $flat->search(fn (Lesson $lesson) => $lesson->id === $current->id);

        if ($index === false) {
            return null;
        }

        return $flat->get($index + 1);
    }
}
