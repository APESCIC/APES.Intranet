<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Hr\Models\ChecklistTemplate;
use Modules\Hr\Models\HrRecord;
use Modules\Hr\Models\MemberChecklist;
use Modules\Hr\Models\MemberChecklistItem;
use Modules\Hr\Services\ChecklistService;
use RuntimeException;

class ChecklistController
{
    public function index(Request $request, ChecklistService $checklists): View
    {
        $checklists->ensureDefaults();

        return view('hr::checklists.index', [
            'open' => MemberChecklist::query()
                ->with(['user', 'items'])
                ->where('status', MemberChecklist::STATUS_OPEN)
                ->latest()
                ->limit(50)
                ->get(),
            'memberTypes' => ChecklistTemplate::MEMBER_TYPES,
            'members' => User::query()->active()->orderBy('name')->limit(200)->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request, ChecklistService $checklists, SensitiveData $sensitive, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'kind' => ['required', Rule::in([ChecklistTemplate::KIND_ONBOARDING, ChecklistTemplate::KIND_OFFBOARDING])],
            'member_type' => ['required', Rule::in(ChecklistTemplate::MEMBER_TYPES)],
        ]);

        $member = User::query()->findOrFail($data['user_id']);
        $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $member->id]));

        try {
            $checklist = $checklists->start($member, $data['kind'], $data['member_type'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['kind' => $e->getMessage()])->withInput();
        }

        $audit->record('hr.checklist.started', $checklist, [
            'kind' => $checklist->kind,
            'member_type' => $checklist->member_type,
            'it_ticket_id' => $checklist->it_ticket_id,
        ], $request->user());

        return redirect()->route('hr.checklists.show', $checklist)
            ->with('status', $checklist->kind === ChecklistTemplate::KIND_OFFBOARDING
                ? 'Offboarding started. An IT ticket was raised for account removal.'
                : 'Onboarding checklist started.');
    }

    public function show(Request $request, MemberChecklist $checklist, SensitiveData $sensitive): View
    {
        $checklist->load(['user', 'items']);
        $sensitive->openHrRecord(HrRecord::query()->firstOrCreate(['user_id' => $checklist->user_id]));

        return view('hr::checklists.show', ['checklist' => $checklist]);
    }

    public function completeItem(Request $request, MemberChecklistItem $item, ChecklistService $checklists, AuditLogger $audit): RedirectResponse
    {
        $item->load('checklist.user');
        abort_unless($item->checklist->status === MemberChecklist::STATUS_OPEN, 404);

        try {
            $checklists->completeItem($item, $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['item' => $e->getMessage()]);
        }

        $audit->record('hr.checklist.item_completed', $item, [
            'checklist_id' => $item->checklist_id,
            'title' => $item->title,
        ], $request->user());

        return back()->with('status', 'Task marked complete.');
    }
}
