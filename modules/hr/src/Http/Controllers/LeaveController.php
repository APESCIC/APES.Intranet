<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Hr\HrPermissions;
use Modules\Hr\Models\Absence;
use Modules\Hr\Models\LeaveRequest;
use Modules\Hr\Services\LeaveService;
use RuntimeException;

class LeaveController
{
    public function index(Request $request, LeaveService $leave): View
    {
        $user = $request->user();
        abort_unless($user?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $mine = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->orderByDesc('starts_on')
            ->limit(20)
            ->get();

        $toDecide = collect();
        if ($user->can(AccessMatrix::TEAM_SUPERVISE) || $user->can(HrPermissions::ACCESS)) {
            $toDecide = LeaveRequest::query()
                ->with('user')
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->when(
                    ! $user->can(HrPermissions::ACCESS) && ! $user->hasRole(RoleName::Admin->value),
                    fn ($q) => $q->whereHas('user', fn ($u) => $u->where('manager_id', $user->id)),
                )
                ->orderBy('starts_on')
                ->get();
        }

        return view('hr::leave.index', [
            'balance' => $leave->balanceFor($user),
            'mine' => $mine,
            'toDecide' => $toDecide,
            'canRecordAbsence' => $user->hasAnyRole([RoleName::Hr->value, RoleName::Admin->value]),
            'people' => $user->hasAnyRole([RoleName::Hr->value, RoleName::Admin->value])
                ? User::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function store(Request $request, LeaveService $leave, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $data = $request->validate([
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $row = $leave->request(
                $user,
                Carbon::parse($data['starts_on']),
                Carbon::parse($data['ends_on']),
                $data['reason'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['starts_on' => $e->getMessage()])->withInput();
        }

        $audit->record('hr.leave.requested', $row, ['member_id' => $user->id]);

        return back()->with('status', 'Leave request submitted.');
    }

    public function approve(Request $request, LeaveRequest $leaveRequest, LeaveService $leave, AuditLogger $audit): RedirectResponse
    {
        $this->authoriseDecide($request, $leaveRequest);

        try {
            $leave->approve($leaveRequest, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }

        $audit->record('hr.leave.approved', $leaveRequest, ['member_id' => $leaveRequest->user_id]);

        return back()->with('status', 'Leave approved. Balance updated.');
    }

    public function decline(Request $request, LeaveRequest $leaveRequest, LeaveService $leave, AuditLogger $audit): RedirectResponse
    {
        $this->authoriseDecide($request, $leaveRequest);

        try {
            $leave->decline($leaveRequest, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['leave' => $e->getMessage()]);
        }

        $audit->record('hr.leave.declined', $leaveRequest, ['member_id' => $leaveRequest->user_id]);

        return back()->with('status', 'Leave declined.');
    }

    public function storeAbsence(Request $request, AuditLogger $audit): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor?->hasAnyRole([RoleName::Hr->value, RoleName::Admin->value]), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'absent_on' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $absence = Absence::query()->create([
            ...$data,
            'recorded_by' => $actor->id,
        ]);

        $audit->record('hr.absence.recorded', $absence, ['member_id' => $absence->user_id]);

        return back()->with('status', 'Absence recorded.');
    }

    private function authoriseDecide(Request $request, LeaveRequest $leaveRequest): void
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($user->can(HrPermissions::ACCESS) || $user->hasRole(RoleName::Admin->value)) {
            return;
        }

        abort_unless(
            $user->can(AccessMatrix::TEAM_SUPERVISE)
            && (int) $leaveRequest->user?->manager_id === (int) $user->id,
            403
        );
    }
}
