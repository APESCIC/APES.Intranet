<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Hr\Models\HrRecord;
use Modules\Hr\Models\TrainingCourse;
use Modules\Hr\Services\TrainingService;

class TrainingController
{
    public function mine(Request $request, TrainingService $training): View
    {
        return view('hr::training.mine', [
            'rows' => $training->forMember($request->user()),
            'lmsBase' => config('hr.lms_base_url'),
        ]);
    }

    public function overdue(Request $request, TrainingService $training, SensitiveData $sensitive): View
    {
        $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $request->user()->id]));

        return view('hr::training.overdue', [
            'rows' => $training->overdueReport(),
            'members' => User::query()->active()->orderBy('name')->limit(200)->get(['id', 'name']),
            'courses' => TrainingCourse::query()->where('active', true)->orderBy('title')->get(['id', 'title', 'code']),
        ]);
    }

    public function storeCompletion(Request $request, TrainingService $training, SensitiveData $sensitive, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'course_id' => ['required', 'integer', Rule::exists('hr_training_courses', 'id')],
            'completed_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $member = User::query()->findOrFail($data['user_id']);
        $course = TrainingCourse::query()->findOrFail($data['course_id']);
        $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $member->id]));

        $completion = $training->recordCompletion(
            $member,
            $course,
            Carbon::parse($data['completed_on']),
            $request->user(),
            $data['notes'] ?? null,
        );

        $audit->record('hr.training.completion_recorded', $completion, [
            'user_id' => $member->id,
            'course_id' => $course->id,
        ], $request->user());

        return back()->with('status', 'Completion recorded for '.$member->name.'.');
    }
}
