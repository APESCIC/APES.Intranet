<?php

namespace Modules\Learning\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Learning\Models\Course;
use Modules\Learning\Services\GradeViewExportService;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GradeViewController extends Controller
{
    public function __construct(private readonly GradeViewExportService $grades) {}

    public function mine(Request $request): View
    {
        return view('learning::grades.mine', [
            'rows' => $this->grades->learnerReleasedGrades($request->user()),
        ]);
    }

    public function export(Request $request, Course $course): StreamedResponse
    {
        Gate::authorize('learning.grade.view', $course->id);

        try {
            return $this->grades->exportCsv($request->user(), $course);
        } catch (Throwable $e) {
            abort(403, $e->getMessage());
        }
    }
}
