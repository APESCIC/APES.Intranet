<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Site;
use Modules\Operations\Services\Clock;
use Modules\Operations\Services\Geofence;
use RuntimeException;

class ClockController
{
    public function index(Request $request, Clock $clock, Geofence $geofence): View
    {
        $user = $request->user();
        $shifts = $clock->shiftsToClockInto($user);
        $sites = Site::query()->active()->orderBy('name')->get();

        return view('operations::clock.index', [
            'open' => $clock->openRecord($user),
            'shifts' => $shifts,
            'sites' => $sites,
            'geofenceShiftIds' => $shifts->filter(fn (Shift $s) => $geofence->appliesTo($s->site))->pluck('id'),
            'geofenceSiteIds' => $sites->filter(fn (Site $s) => $geofence->appliesTo($s))->pluck('id'),
            'geofenceOn' => $geofence->isEnabled(),
            'recent' => ClockRecord::query()
                ->with(['breaks', 'shift', 'site'])
                ->where('user_id', $user->getKey())
                ->where('clock_in_at', '>=', now()->subDays(14))
                ->latest('clock_in_at')
                ->get(),
        ]);
    }

    public function clockIn(Request $request, Clock $clock): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['nullable', 'string', 'regex:/^(shift|site):\d+$/'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        [$kind, $id] = filled($data['target'] ?? null) ? explode(':', $data['target']) : [null, null];
        $shift = $kind === 'shift' ? Shift::query()->with('site')->find($id) : null;
        $site = $kind === 'site' ? Site::query()->active()->find($id) : null;
        if ($kind !== null && $shift === null && $site === null) {
            return back()->withErrors(['clock' => 'Choose a shift or site from the list.']);
        }

        try {
            $clock->clockIn(
                $request->user(),
                $shift,
                $site,
                isset($data['latitude']) ? (float) $data['latitude'] : null,
                isset($data['longitude']) ? (float) $data['longitude'] : null,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['clock' => $e->getMessage()]);
        }

        return back()->with('status', 'Clocked in at '.now()->format('H:i').'.');
    }

    public function startBreak(Request $request, Clock $clock): RedirectResponse
    {
        return $this->attempt(fn () => $clock->startBreak($request->user()), 'Break started at '.now()->format('H:i').'.');
    }

    public function endBreak(Request $request, Clock $clock): RedirectResponse
    {
        return $this->attempt(fn () => $clock->endBreak($request->user()), 'Break ended at '.now()->format('H:i').'.');
    }

    public function clockOut(Request $request, Clock $clock): RedirectResponse
    {
        return $this->attempt(fn () => $clock->clockOut($request->user()), 'Clocked out at '.now()->format('H:i').'.');
    }

    public function team(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can(AccessMatrix::TEAM_SUPERVISE), 403);

        $records = ClockRecord::query()
            ->with(['member', 'shift', 'site', 'breaks'])
            ->where(fn ($q) => $q->whereNull('clock_out_at')->orWhere('clock_in_at', '>=', now()->subDays(7)))
            ->latest('clock_in_at')
            ->limit(300)
            ->get()
            ->filter(fn (ClockRecord $record) => (int) $record->user_id !== (int) $user->getKey()
                && Gate::forUser($user)->allows('approveTeamTimesheet', $record))
            ->values();

        return view('operations::clock.team', ['records' => $records]);
    }

    public function correct(Request $request, ClockRecord $record, Clock $clock): RedirectResponse
    {
        abort_unless(Gate::allows('approveTeamTimesheet', $record) && (int) $record->user_id !== (int) $request->user()->getKey(), 403);
        abort_unless($record->isOpen(), 422);

        $data = $request->validate(['clock_out_at' => ['required', 'date']]);

        try {
            $clock->correctClockOut($record->load('breaks'), CarbonImmutable::parse($data['clock_out_at']), $request->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['clock_out_at' => $e->getMessage()]);
        }

        return back()->with('status', 'Clock-out recorded.');
    }

    private function attempt(callable $action, string $done): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->withErrors(['clock' => $e->getMessage()]);
        }

        return back()->with('status', $done);
    }
}
