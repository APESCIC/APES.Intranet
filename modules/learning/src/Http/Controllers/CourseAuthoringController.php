<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\Section;
use Modules\Learning\Services\CourseAuthoringService;
use RuntimeException;

class CourseAuthoringController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Course::class);

        return view('learning::courses.index', [
            'courses' => Course::query()->withCount('sections')->orderBy('title')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Course::class);

        return view('learning::courses.create');
    }

    public function store(Request $request, CourseAuthoringService $authoring): RedirectResponse
    {
        Gate::authorize('create', Course::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'slug' => ['nullable', 'string', 'max:200'],
        ]);

        $course = $authoring->createCourse($data, $request->user());

        return redirect()->route('learning.courses.show', $course)
            ->with('status', 'Course draft created.');
    }

    public function show(Request $request, Course $course): View
    {
        Gate::authorize('view', $course);

        $course->load(['sections.lessons.currentVersion']);

        return view('learning::courses.show', [
            'course' => $course,
            'canAuthor' => $request->user()->can('update', $course),
        ]);
    }

    public function storeSection(Request $request, Course $course, CourseAuthoringService $authoring): RedirectResponse
    {
        Gate::authorize('update', $course);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
        ]);

        $authoring->addSection($course, $data['title']);

        return back()->with('status', 'Section added.');
    }

    public function storeLesson(Request $request, Course $course, Section $section, CourseAuthoringService $authoring): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($section->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
        ]);

        $lesson = $authoring->addLesson($section, $data['title'], $data['body'] ?? null);

        return redirect()->route('learning.courses.lessons.edit', [$course, $lesson])
            ->with('status', 'Lesson added.');
    }

    public function editLesson(Request $request, Course $course, Lesson $lesson): View
    {
        Gate::authorize('update', $course);
        abort_unless($lesson->section->course_id === $course->id, 404);

        return view('learning::courses.lesson-edit', [
            'course' => $course,
            'lesson' => $lesson->load('versions'),
        ]);
    }

    public function updateLesson(Request $request, Course $course, Lesson $lesson, CourseAuthoringService $authoring): RedirectResponse
    {
        Gate::authorize('update', $course);
        abort_unless($lesson->section->course_id === $course->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
        ]);

        $authoring->updateLessonDraft($lesson, $data['title'], $data['body'] ?? null);

        return back()->with('status', 'Draft saved (not published yet).');
    }

    public function publishLesson(Request $request, Course $course, Lesson $lesson, CourseAuthoringService $authoring): RedirectResponse
    {
        Gate::authorize('publish', $course);
        abort_unless($lesson->section->course_id === $course->id, 404);

        try {
            $version = $authoring->publishLesson($lesson, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }

        return back()->with('status', "Published version {$version->version_number}. Learners already in progress keep their locked version.");
    }
}
