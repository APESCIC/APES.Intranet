<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Learning\Models\Enrolment;
use Modules\Learning\Services\ProgressService;

class MyLearningController extends Controller
{
    public function __invoke(Request $request, ProgressService $progress): View
    {
        $user = $request->user();

        $enrolments = Enrolment::query()
            ->with('course')
            ->where('user_id', $user->id)
            ->where('status', Enrolment::STATUS_ACTIVE)
            ->orderByDesc('enrolled_at')
            ->get()
            ->filter(fn (Enrolment $enrolment) => $enrolment->isWithinDates());

        $courses = $enrolments->map(function (Enrolment $enrolment) use ($user, $progress) {
            $course = $enrolment->course;
            $summary = $progress->courseProgressSummary($user, $course);

            return [
                'course' => $course,
                'enrolment' => $enrolment,
                'summary' => $summary,
                'complete' => $progress->isCourseComplete($user, $course),
            ];
        });

        return view('learning::mine', [
            'title' => 'My learning',
            'courses' => $courses,
        ]);
    }
}
