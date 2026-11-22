<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Services\LearningReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LearningReportController extends Controller
{
    public function __construct(private readonly LearningReportService $reports) {}

    public function index(Request $request): View
    {
        return view('learning::reports.index', [
            'title' => 'Learning reports',
            'courses' => $this->reports->reportableCourses($request->user()),
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        try {
            $completion = $this->reports->completionReport($request->user(), $course);
            $distribution = $this->reports->gradeDistribution($request->user(), $course);
        } catch (Throwable $e) {
            abort(403, $e->getMessage());
        }

        return view('learning::reports.show', [
            'title' => 'Reports: '.$course->title,
            'course' => $course,
            'completion' => $completion,
            'distribution' => $distribution,
            'minGroupSize' => $this->reports->minGroupSize(),
        ]);
    }

    public function exportCompletions(Request $request, Course $course): StreamedResponse
    {
        try {
            return $this->reports->exportCompletionCsv($request->user(), $course);
        } catch (Throwable $e) {
            abort(403, $e->getMessage());
        }
    }
}
