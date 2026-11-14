<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Hr\Models\Appraisal;
use Modules\Hr\Models\AppraisalCycle;
use Modules\Hr\Models\HrRecord;
use Modules\Hr\Services\AppraisalService;
use RuntimeException;

class AppraisalController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Appraisal::query()->with(['cycle', 'user', 'manager'])->latest();

        if (! $user->can(AccessMatrix::HR_ACCESS)) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('manager_id', $user->id)
                    ->orWhereHas('user', fn ($u) => $u->where('manager_id', $user->id));
            });
        }

        return view('hr::appraisals.index', [
            'appraisals' => $query->limit(50)->get(),
            'cycles' => AppraisalCycle::query()->orderByDesc('year')->get(),
            'canManage' => $user->can(AccessMatrix::HR_ACCESS),
            'members' => $user->can(AccessMatrix::HR_ACCESS)
                ? User::query()->active()->orderBy('name')->limit(200)->get(['id', 'name', 'manager_id'])
                : collect(),
        ]);
    }

    public function storeCycle(Request $request, AppraisalService $appraisals, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can(AccessMatrix::HR_ACCESS), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
        ]);

        $cycle = $appraisals->openCycle($data['name'], (int) $data['year'], $data['starts_on'], $data['ends_on']);
        $audit->record('hr.appraisal.cycle_opened', $cycle, ['year' => $cycle->year], $request->user());

        return back()->with('status', 'Appraisal cycle opened.');
    }

    public function store(Request $request, AppraisalService $appraisals, SensitiveData $sensitive, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can(AccessMatrix::HR_ACCESS), 403);

        $data = $request->validate([
            'cycle_id' => ['required', 'integer', Rule::exists('hr_appraisal_cycles', 'id')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
        ]);

        $member = User::query()->findOrFail($data['user_id']);
        $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $member->id]));
        $cycle = AppraisalCycle::query()->findOrFail($data['cycle_id']);

        try {
            $appraisal = $appraisals->startFor($cycle, $member);
        } catch (RuntimeException $e) {
            return back()->withErrors(['cycle_id' => $e->getMessage()]);
        }

        $audit->record('hr.appraisal.started', $appraisal, ['cycle_id' => $cycle->id], $request->user());

        return redirect()->route('hr.appraisals.show', $appraisal)->with('status', 'Appraisal started.');
    }

    public function show(Request $request, Appraisal $appraisal, SensitiveData $sensitive, AuditLogger $audit): View
    {
        $appraisal->load(['cycle', 'user', 'manager']);
        abort_unless($appraisal->canBeViewedBy($request->user()), 403);

        // HR opens through SensitiveData; member and manager get a plain audit entry.
        if ($request->user()->can(AccessMatrix::HR_ACCESS)) {
            $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $appraisal->user_id]));
        } else {
            $audit->record('hr.appraisal.viewed', $appraisal);
        }

        return view('hr::appraisals.show', [
            'appraisal' => $appraisal,
            'canEdit' => $appraisal->canBeEditedBy($request->user()),
            'isMember' => (int) $request->user()->id === (int) $appraisal->user_id,
            'isManager' => (int) $request->user()->id === (int) $appraisal->manager_id
                || (int) $request->user()->id === (int) $appraisal->user->manager_id,
            'isHr' => $request->user()->can(AccessMatrix::HR_ACCESS),
        ]);
    }

    public function updateSelf(Request $request, Appraisal $appraisal, AppraisalService $appraisals, AuditLogger $audit): RedirectResponse
    {
        abort_unless($appraisal->canBeViewedBy($request->user()), 403);
        $data = $request->validate(['self_review' => ['required', 'string', 'max:10000']]);

        try {
            $appraisals->saveSelfReview($appraisal, $data['self_review'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['self_review' => $e->getMessage()]);
        }

        $audit->record('hr.appraisal.self_review', $appraisal, [], $request->user());

        return back()->with('status', 'Self review saved.');
    }

    public function updateManager(Request $request, Appraisal $appraisal, AppraisalService $appraisals, AuditLogger $audit): RedirectResponse
    {
        abort_unless($appraisal->canBeViewedBy($request->user()), 403);
        $data = $request->validate(['manager_review' => ['required', 'string', 'max:10000']]);

        try {
            $appraisals->saveManagerReview($appraisal, $data['manager_review'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['manager_review' => $e->getMessage()]);
        }

        $audit->record('hr.appraisal.manager_review', $appraisal, [], $request->user());

        return back()->with('status', 'Manager review saved.');
    }

    public function lock(Request $request, Appraisal $appraisal, AppraisalService $appraisals, AuditLogger $audit): RedirectResponse
    {
        abort_unless($appraisal->canBeViewedBy($request->user()), 403);

        try {
            $appraisals->completeAndLock($appraisal, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['lock' => $e->getMessage()]);
        }

        $audit->record('hr.appraisal.locked', $appraisal, [], $request->user());

        return back()->with('status', 'Appraisal completed and locked.');
    }
}
