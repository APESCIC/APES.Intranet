<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\ChecklistRunItem;
use Modules\Operations\Models\ChecklistTemplate;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Services\Checklists;
use Modules\Operations\Support\TeamAccess;
use Modules\Operations\Support\Uploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChecklistController
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $mine = ChecklistRun::query()
            ->with(['template', 'shift.site'])
            ->whereHas('shift', fn ($q) => $q->published()->where('user_id', $user->getKey()))
            ->where(fn ($q) => $q->whereNull('completed_at')->orWhere('completed_at', '>=', now()->subDays(7)))
            ->where('due_at', '<=', now()->addDays(2))
            ->orderBy('due_at')
            ->get();

        $team = $user->can(AccessMatrix::TEAM_SUPERVISE)
            ? ChecklistRun::query()
                ->with(['template', 'shift.member', 'site', 'completer'])
                ->whereHas('shift', fn ($q) => $q->published())
                ->whereBetween('due_at', [now()->subDays(7), now()->addDay()])
                ->whereNotIn('id', $mine->pluck('id'))
                ->orderByDesc('due_at')
                ->limit(300)
                ->get()
                ->filter(fn (ChecklistRun $run) => Gate::forUser($user)->allows('viewTeamChecklist', $run))
                ->values()
            : collect();

        return view('operations::checklists.index', [
            'mine' => $mine,
            'team' => $team,
            'canManage' => $user->can(OperationsPermissions::CHECKLISTS_MANAGE),
        ]);
    }

    public function show(Request $request, ChecklistRun $run): View
    {
        abort_unless($request->user()->can('view', $run), 403);

        return view('operations::checklists.run', [
            'run' => $run->load(['template', 'items', 'shift.member', 'site', 'completer']),
            'canUpdate' => $request->user()->can('update', $run),
        ]);
    }

    public function update(Request $request, ChecklistRun $run, Checklists $checklists): RedirectResponse
    {
        abort_unless($request->user()->can('update', $run), 403);

        $data = $request->validate([
            'items' => ['array'],
            'items.*.done' => ['nullable', 'boolean'],
            'items.*.note' => ['nullable', 'string', 'max:1000'],
            'photos' => ['array'],
            'photos.*' => Uploads::photoRules(false),
            'action' => ['required', 'in:save,complete'],
        ]);

        try {
            $checklists->save($run->load('items'), $data['items'] ?? [], $request->file('photos', []), $request->user(), $data['action'] === 'complete');
        } catch (RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return $data['action'] === 'complete'
            ? redirect()->route('operations.checklists.runs.show', $run)->with('status', 'Checklist completed. It is now read-only.')
            : back()->with('status', 'Progress saved.');
    }

    public function photo(Request $request, ChecklistRunItem $item): StreamedResponse
    {
        abort_unless($item->photo_path !== null && $request->user()->can('view', $item->run), 404);

        return Uploads::response($item->photo_path);
    }

    public function templates(Request $request): View
    {
        abort_unless($request->user()->can(OperationsPermissions::CHECKLISTS_MANAGE), 403);
        $sites = TeamAccess::manageableSites($request->user(), OperationsPermissions::CHECKLISTS_MANAGE);

        return view('operations::checklists.templates', [
            'sites' => $sites,
            'shiftTemplates' => ShiftTemplate::query()->whereIn('site_id', $sites->pluck('id'))->where('active', true)->orderBy('weekday')->orderBy('starts_at')->get(),
            'templates' => ChecklistTemplate::query()->with(['site', 'shiftTemplate'])->whereIn('site_id', $sites->pluck('id'))->orderBy('site_id')->orderBy('due_time')->get(),
        ]);
    }

    public function storeTemplate(Request $request, Checklists $checklists, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer', Rule::exists('operations_sites', 'id')],
            'shift_template_id' => ['nullable', 'integer', Rule::exists('operations_shift_templates', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'due_time' => ['required', 'date_format:H:i'],
            'items' => ['required', 'string', 'max:5000'],
        ]);

        $site = Site::query()->findOrFail($data['site_id']);
        abort_unless(TeamAccess::canManageSite($request->user(), $site, OperationsPermissions::CHECKLISTS_MANAGE), 403);

        if (isset($data['shift_template_id']) && (int) ShiftTemplate::query()->whereKey($data['shift_template_id'])->value('site_id') !== $site->id) {
            return back()->withErrors(['shift_template_id' => 'Choose a shift template from the same site.'])->withInput();
        }

        $items = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $data['items']) ?: [])));
        if ($items === []) {
            return back()->withErrors(['items' => 'Add at least one check, one per line.'])->withInput();
        }

        $template = ChecklistTemplate::query()->create([
            'site_id' => $site->id,
            'shift_template_id' => $data['shift_template_id'] ?? null,
            'name' => $data['name'],
            'due_time' => $data['due_time'],
            'items' => $items,
            'active' => true,
            'created_by' => $request->user()->getKey(),
        ]);
        $audit->record('operations.checklist_template_created', $template, ['site_id' => $site->id, 'items' => count($items)]);
        $attached = $checklists->scheduleTemplate($template);

        return back()->with('status', 'Checklist saved and added to '.$attached.' published shift(s).');
    }

    public function retireTemplate(Request $request, ChecklistTemplate $template, AuditLogger $audit): RedirectResponse
    {
        abort_unless(TeamAccess::canManageSite($request->user(), $template->site, OperationsPermissions::CHECKLISTS_MANAGE), 403);

        $template->update(['active' => false]);
        $audit->record('operations.checklist_template_retired', $template, ['site_id' => $template->site_id]);

        return back()->with('status', 'Checklist retired. Existing checklists and history are kept.');
    }
}
