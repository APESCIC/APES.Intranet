<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Operations\Models\Timesheet;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Services\Timesheets;
use Modules\Operations\Services\VolunteerHours;
use RuntimeException;

class TimesheetController
{
    public function index(Request $request): View
    {
        return view('operations::timesheets.index', [
            'timesheets' => Timesheet::query()
                ->where('user_id', $request->user()->getKey())
                ->orderByDesc('period_start')
                ->limit(26)
                ->get(),
        ]);
    }

    public function build(Request $request, Timesheets $timesheets): RedirectResponse
    {
        $data = $request->validate(['week_of' => ['required', 'date', 'before_or_equal:today']]);

        try {
            $timesheet = $timesheets->build($request->user(), CarbonImmutable::parse($data['week_of']));
        } catch (RuntimeException $e) {
            return back()->withErrors(['week_of' => $e->getMessage()]);
        }

        return redirect()->route('operations.timesheets.show', $timesheet)->with('status', 'Timesheet built from your clock records.');
    }

    public function show(Request $request, Timesheet $timesheet): View
    {
        $this->authoriseView($request, $timesheet);

        return view('operations::timesheets.show', [
            'timesheet' => $timesheet->load(['entries', 'member', 'reviewer']),
            'isOwner' => (int) $timesheet->user_id === (int) $request->user()->getKey(),
            'canReview' => $this->canReview($request, $timesheet),
        ]);
    }

    public function submit(Request $request, Timesheet $timesheet, Timesheets $timesheets): RedirectResponse
    {
        abort_unless((int) $timesheet->user_id === (int) $request->user()->getKey(), 403);

        try {
            $timesheets->build($request->user(), $timesheet->period_start);
            $timesheets->submit($timesheet->refresh(), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['timesheet' => $e->getMessage()]);
        }

        return back()->with('status', 'Timesheet submitted. Your manager has been emailed.');
    }

    public function review(Request $request): View
    {
        abort_unless($request->user()->can(AccessMatrix::TEAM_SUPERVISE), 403);

        return view('operations::timesheets.review', [
            'timesheets' => Timesheet::query()
                ->with('member')
                ->where('status', Timesheet::STATUS_SUBMITTED)
                ->where('user_id', '!=', $request->user()->getKey())
                ->orderBy('period_start')
                ->get()
                ->filter(fn (Timesheet $t) => Gate::allows('approveTeamTimesheet', $t))
                ->values(),
        ]);
    }

    public function decide(Request $request, Timesheet $timesheet, Timesheets $timesheets): RedirectResponse
    {
        abort_unless($this->canReview($request, $timesheet), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,return'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $timesheets->review($timesheet, $request->user(), $data['decision'] === 'approve', $data['review_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['review_note' => $e->getMessage()]);
        }

        return back()->with('status', $data['decision'] === 'approve' ? 'Timesheet approved and locked.' : 'Timesheet returned to the member.');
    }

    public function hours(Request $request, VolunteerHours $hours): View
    {
        [$from, $to] = $this->range($request);

        return view('operations::timesheets.hours', [
            'from' => $from,
            'to' => $to,
            'rows' => $hours->totals($from, $to),
        ]);
    }

    public function hoursCsv(Request $request, VolunteerHours $hours): Response
    {
        [$from, $to] = $this->range($request);

        return response($hours->csv($hours->totals($from, $to)), 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="volunteer-hours-'.$from->toDateString().'-to-'.$to->toDateString().'.csv"',
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request): array
    {
        abort_unless($request->user()->can(OperationsPermissions::HOURS_EXPORT), 403);

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            CarbonImmutable::parse($data['from'] ?? now()->startOfMonth())->startOfDay(),
            CarbonImmutable::parse($data['to'] ?? now())->startOfDay(),
        ];
    }

    private function canReview(Request $request, Timesheet $timesheet): bool
    {
        return (int) $timesheet->user_id !== (int) $request->user()->getKey()
            && Gate::allows('approveTeamTimesheet', $timesheet);
    }

    private function authoriseView(Request $request, Timesheet $timesheet): void
    {
        abort_unless(
            (int) $timesheet->user_id === (int) $request->user()->getKey() || $this->canReview($request, $timesheet),
            403,
        );
    }
}
