<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Audit\AuditLogger;
use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Services\ClashDetector;
use Modules\Operations\Services\RotaBuilder;
use Modules\Operations\Services\RotaPublisher;
use Modules\Operations\Support\RotaWeekGrid;
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

        $week->load(['site', 'shifts.member', 'shifts.checklistRuns.template']);

        return view('operations::rota.week', [
            'week' => $week,
            'clashes' => $clashes->forWeek($week),
            'grid' => RotaWeekGrid::forWeek($week),
            'templates' => ShiftTemplate::query()
                ->where('site_id', $week->site_id)
                ->where('active', true)
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get(),
            'members' => User::query()->active()->permission(AccessMatrix::INTRANET_ACCESS)->orderBy('name')->get(['id', 'name', 'team']),
        ]);
    }

    public function storeShift(Request $request, RotaWeek $week, AuditLogger $audit, ApprovedLeaveLookup $leave, ClashDetector $clashes): RedirectResponse
    {
        $this->authoriseManage($request, $week);

        $data = $request->validate([
            'shift_template_id' => ['nullable', 'integer', Rule::exists('operations_shift_templates', 'id')],
            'role' => ['required_without:shift_template_id', 'nullable', 'string', 'max:120'],
            'starts_at' => ['required_without:shift_template_id', 'nullable', 'date'],
            'ends_at' => ['required_without:shift_template_id', 'nullable', 'date'],
            'day' => ['nullable', 'date'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $templateId = $data['shift_template_id'] ?? null;
        $role = $data['role'] ?? null;
        $startsAt = isset($data['starts_at']) ? CarbonImmutable::parse($data['starts_at']) : null;
        $endsAt = isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at']) : null;

        if ($templateId !== null) {
            $template = ShiftTemplate::query()->findOrFail($templateId);
            abort_unless((int) $template->site_id === (int) $week->site_id && $template->active, 403);

            $day = isset($data['day'])
                ? CarbonImmutable::parse($data['day'])->startOfDay()
                : CarbonImmutable::parse($week->week_start)->addDays($template->weekday - 1);
            $startsAt = $day->setTimeFromTimeString($template->starts_at);
            $endsAt = $day->setTimeFromTimeString($template->ends_at);
            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                $endsAt = $endsAt->addDay();
            }
            $role = $template->role;
        }

        abort_if($startsAt === null || $endsAt === null || $role === null, 422);
        abort_unless($endsAt->greaterThan($startsAt), 422);

        $userId = $data['user_id'] ?? null;
        if ($userId !== null && $leave->overlaps((int) $userId, $startsAt, $endsAt)) {
            return back()->withErrors([
                'user_id' => 'That person has approved leave covering this shift.',
            ])->withInput();
        }

        $shift = $week->shifts()->create([
            'site_id' => $week->site_id,
            'shift_template_id' => $templateId,
            'role' => $role,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'user_id' => $userId,
        ]);
        $audit->record('operations.shift_added', $shift, ['rota_week_id' => $week->id, 'user_id' => $shift->user_id]);

        $found = $userId !== null ? $clashes->forShift($shift) : [];

        return back()->with('status', $found === []
            ? 'Shift added.'
            : 'Shift added with a clash: '.implode('; ', $found).'.');
    }

    public function updateShift(Request $request, Shift $shift, AuditLogger $audit, ClashDetector $clashes, ApprovedLeaveLookup $leave): RedirectResponse
    {
        $week = $shift->rotaWeek;
        $this->authoriseManage($request, $week);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'role' => ['sometimes', 'string', 'max:120'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date'],
        ]);

        $startsAt = isset($data['starts_at']) ? CarbonImmutable::parse($data['starts_at']) : $shift->starts_at;
        $endsAt = isset($data['ends_at']) ? CarbonImmutable::parse($data['ends_at']) : $shift->ends_at;
        abort_unless($endsAt->greaterThan($startsAt), 422);

        $userId = array_key_exists('user_id', $data) ? $data['user_id'] : $shift->user_id;
        if ($userId !== null && $leave->overlaps((int) $userId, $startsAt, $endsAt)) {
            return back()->withErrors([
                'user_id' => 'That person has approved leave covering this shift.',
            ])->withInput();
        }

        $before = $shift->user_id;
        $payload = [
            'user_id' => $userId,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ];
        if (isset($data['role'])) {
            $payload['role'] = $data['role'];
        }

        $shift->update($payload);
        $audit->record('operations.shift_assigned', $shift, [
            'from_user_id' => $before,
            'to_user_id' => $shift->user_id,
            'role' => $shift->role,
            'starts_at' => $shift->starts_at->toDateTimeString(),
            'ends_at' => $shift->ends_at->toDateTimeString(),
        ]);

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
