<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Site;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Services\ClashDetector;
use Modules\Operations\Services\RotaBuilder;
use Modules\Operations\Services\RotaPublisher;
use Modules\Operations\Support\TeamAccess;
use RuntimeException;

class RotaController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $sites = TeamAccess::manageableSites($user, OperationsPermissions::ROTA_MANAGE);

        return view('operations::rota.index', [
            'myShifts' => Shift::query()
                ->published()
                ->with(['site', 'checklistRuns.template'])
                ->where('user_id', $user->getKey())
                ->where('ends_at', '>=', now()->startOfDay())
                ->orderBy('starts_at')
                ->limit(50)
                ->get(),
            'sites' => $sites,
            'weeks' => RotaWeek::query()
                ->with('site')
                ->withCount(['shifts', 'shifts as open_shifts_count' => fn ($q) => $q->whereNull('user_id')])
                ->whereIn('site_id', $sites->pluck('id'))
                ->where('week_start', '>=', now()->subWeeks(4)->toDateString())
                ->orderByDesc('week_start')
                ->get(),
        ]);
    }

    public function storeWeek(Request $request, RotaBuilder $builder): RedirectResponse
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer', Rule::exists('operations_sites', 'id')],
            'week_of' => ['required', 'date'],
        ]);

        $site = Site::query()->findOrFail($data['site_id']);
        abort_unless(TeamAccess::canManageSite($request->user(), $site, OperationsPermissions::ROTA_MANAGE), 403);

        try {
            $week = $builder->createWeekFromTemplates($site, CarbonImmutable::parse($data['week_of']), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['week_of' => $e->getMessage()])->withInput();
        }

        return redirect()->route('operations.rota.weeks.show', $week)->with('status', 'Draft rota created from the templates.');
    }

    public function showWeek(Request $request, RotaWeek $week, ClashDetector $clashes): View
    {
        $this->authoriseManage($request, $week);

        $week->load(['site', 'shifts.member']);

        return view('operations::rota.week', [
            'week' => $week,
            'clashes' => $clashes->forWeek($week),
            'members' => User::query()->active()->permission(AccessMatrix::INTRANET_ACCESS)->orderBy('name')->get(['id', 'name', 'team']),
        ]);
    }

    public function storeShift(Request $request, RotaWeek $week, AuditLogger $audit): RedirectResponse
    {
        $this->authoriseManage($request, $week);

        $data = $request->validate([
            'role' => ['required', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $shift = $week->shifts()->create($data + ['site_id' => $week->site_id]);
        $audit->record('operations.shift_added', $shift, ['rota_week_id' => $week->id, 'user_id' => $shift->user_id]);

        return back()->with('status', 'Shift added.');
    }

    public function updateShift(Request $request, Shift $shift, AuditLogger $audit, ClashDetector $clashes): RedirectResponse
    {
        $week = $shift->rotaWeek;
        $this->authoriseManage($request, $week);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $before = $shift->user_id;
        $shift->update(['user_id' => $data['user_id'] ?? null]);
        $audit->record('operations.shift_assigned', $shift, ['from_user_id' => $before, 'to_user_id' => $shift->user_id]);

        $found = $clashes->forShift($shift);

        return back()->with('status', $found === []
            ? 'Shift saved.'
            : 'Shift saved with a clash: '.implode('; ', $found).'.');
    }

    public function destroyShift(Request $request, Shift $shift, AuditLogger $audit): RedirectResponse
    {
        $this->authoriseManage($request, $shift->rotaWeek);

        $audit->record('operations.shift_removed', $shift, ['rota_week_id' => $shift->rota_week_id, 'user_id' => $shift->user_id]);
        $shift->delete();

        return back()->with('status', 'Shift removed.');
    }

    public function publish(Request $request, RotaWeek $week, RotaPublisher $publisher): RedirectResponse
    {
        $this->authoriseManage($request, $week);

        $publisher->publish($week, $request->user());

        return back()->with('status', 'Rota published. Members with shifts have been emailed.');
    }

    private function authoriseManage(Request $request, ?RotaWeek $week): void
    {
        abort_if($week === null, 404);
        $week->loadMissing('site');
        abort_unless(TeamAccess::canManageSite($request->user(), $week->site, OperationsPermissions::ROTA_MANAGE), 403);
    }
}
