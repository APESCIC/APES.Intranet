<?php

namespace Modules\Learning\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Learning\LearningPermissions;
use Modules\Learning\Models\Course;
use Modules\Learning\Services\EnrolmentService;
use RuntimeException;

class EnrolmentController extends Controller
{
    public function index(Request $request, Course $course, EnrolmentService $enrolments): View
    {
        Gate::authorize('enrol', $course);

        return view('learning::courses.enrolments', [
            'course' => $course->load(['groupRules', 'prerequisites.prerequisite', 'cohorts']),
            'enrolments' => $enrolments->enrolmentsForCourse($course),
            'canonicalGroups' => array_keys(config('intranet.group_roles', [])),
            'users' => User::query()->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
            'courses' => Course::query()->where('id', '!=', $course->id)->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function storeManual(Request $request, Course $course, EnrolmentService $enrolments): RedirectResponse
    {
        Gate::authorize('enrol', $course);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'cohort_id' => ['nullable', 'exists:learning_cohorts,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $learner = User::query()->findOrFail($data['user_id']);
        $enrolments->enrolManual($course, $learner, $request->user(), $data);

        return back()->with('status', 'Learner enrolled.');
    }

    public function storeGroupRule(Request $request, Course $course, EnrolmentService $enrolments): RedirectResponse
    {
        Gate::authorize('enrol', $course);

        $data = $request->validate([
            'directory_group' => ['required', 'string', 'max:100'],
            'cohort_id' => ['nullable', 'exists:learning_cohorts,id'],
        ]);

        try {
            $cohort = isset($data['cohort_id'])
                ? $course->cohorts()->whereKey($data['cohort_id'])->first()
                : null;
            $enrolments->attachGroupRule($course, $data['directory_group'], $cohort);
            $enrolments->syncGroupEnrolmentsForCourse($course);
        } catch (RuntimeException $e) {
            return back()->withErrors(['directory_group' => $e->getMessage()]);
        }

        return back()->with('status', 'Group rule saved and enrolments synced.');
    }

    public function storePrerequisite(Request $request, Course $course, EnrolmentService $enrolments): RedirectResponse
    {
        Gate::authorize('enrol', $course);

        $data = $request->validate([
            'prerequisite_course_id' => ['required', 'exists:learning_courses,id', 'not_in:'.$course->id],
        ]);

        $prerequisite = Course::query()->findOrFail($data['prerequisite_course_id']);

        try {
            $enrolments->addPrerequisite($course, $prerequisite);
        } catch (RuntimeException $e) {
            return back()->withErrors(['prerequisite_course_id' => $e->getMessage()]);
        }

        return back()->with('status', 'Prerequisite added.');
    }

    public function selfEnrol(Request $request, Course $course, EnrolmentService $enrolments): RedirectResponse
    {
        abort_unless($request->user()->can(LearningPermissions::ACCESS), 403);

        try {
            $enrolments->enrolSelf($course, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['enrol' => $e->getMessage()]);
        }

        return redirect()->route('learning.courses.show', $course)
            ->with('status', 'You are enrolled.');
    }

    public function sync(Request $request, Course $course, EnrolmentService $enrolments): RedirectResponse
    {
        Gate::authorize('enrol', $course);

        $enrolments->syncGroupEnrolmentsForCourse($course);

        return back()->with('status', 'Group enrolments synced.');
    }
}
