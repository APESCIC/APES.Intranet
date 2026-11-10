<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftRequest;
use Modules\Operations\Services\ShiftRequests;
use RuntimeException;

class ShiftRequestController
{
    public function open(Request $request): View
    {
        return view('operations::rota.open', [
            'shifts' => Shift::query()
                ->published()
                ->with('site')
                ->whereNull('user_id')
                ->where('starts_at', '>', now())
                ->orderBy('starts_at')
                ->limit(100)
                ->get(),
            'pendingShiftIds' => ShiftRequest::query()
                ->where('requester_id', $request->user()->getKey())
                ->where('status', ShiftRequest::STATUS_PENDING)
                ->pluck('shift_id'),
        ]);
    }

    public function claim(Request $request, Shift $shift, ShiftRequests $requests): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        try {
            $requests->claim($shift, $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['claim' => $e->getMessage()]);
        }

        return redirect()->route('operations.rota.requests.index')->with('status', 'Claim sent. A manager will approve or decline it.');
    }

    public function createSwap(Request $request, Shift $shift): View
    {
        abort_unless((int) $shift->user_id === (int) $request->user()->getKey(), 403);

        return view('operations::rota.swap', [
            'shift' => $shift->load('site'),
            'options' => Shift::query()
                ->published()
                ->with('member')
                ->where('site_id', $shift->site_id)
                ->whereNotNull('user_id')
                ->where('user_id', '!=', $request->user()->getKey())
                ->where('starts_at', '>', now())
                ->whereBetween('starts_at', [$shift->starts_at->subDays(14), $shift->starts_at->addDays(14)])
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function storeSwap(Request $request, Shift $shift, ShiftRequests $requests): RedirectResponse
    {
        $data = $request->validate([
            'target_shift_id' => ['required', 'integer', Rule::exists('operations_shifts', 'id')],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $requests->swap($shift, Shift::query()->findOrFail($data['target_shift_id']), $request->user(), $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['target_shift_id' => $e->getMessage()]);
        }

        return redirect()->route('operations.rota.requests.index')->with('status', 'Swap request sent. A manager will approve or decline it.');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $toDecide = $user->can(AccessMatrix::TEAM_SUPERVISE)
            ? ShiftRequest::query()
                ->with(['requester', 'shift.site', 'targetShift.member'])
                ->where('status', ShiftRequest::STATUS_PENDING)
                ->where('requester_id', '!=', $user->getKey())
                ->oldest()
                ->get()
                ->filter(fn (ShiftRequest $r) => Gate::forUser($user)->allows('approveTeamSwap', $r))
                ->values()
            : collect();

        return view('operations::rota.requests', [
            'toDecide' => $toDecide,
            'mine' => ShiftRequest::query()
                ->with(['shift.site', 'targetShift.member', 'decider'])
                ->where('requester_id', $user->getKey())
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function decide(Request $request, ShiftRequest $shiftRequest, ShiftRequests $requests): RedirectResponse
    {
        abort_unless(Gate::allows('approveTeamSwap', $shiftRequest), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,decline'],
            'decision_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $requests->decide($shiftRequest, $request->user(), $data['decision'] === 'approve', $data['decision_note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['decision' => $e->getMessage()]);
        }

        return back()->with('status', $data['decision'] === 'approve' ? 'Approved. The rota is updated and everyone is told.' : 'Declined. Everyone is told.');
    }

    public function cancel(Request $request, ShiftRequest $shiftRequest, ShiftRequests $requests): RedirectResponse
    {
        try {
            $requests->cancel($shiftRequest, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('status', 'Request cancelled.');
    }
}
