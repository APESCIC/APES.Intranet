<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\Site;
use RuntimeException;

final class Clock
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Geofence $geofence,
    ) {}

    public function openRecord(User $member): ?ClockRecord
    {
        return ClockRecord::query()
            ->with(['breaks', 'shift', 'site'])
            ->where('user_id', $member->getKey())
            ->whereNull('clock_out_at')
            ->latest('clock_in_at')
            ->first();
    }

    /**
     * Published shifts the member can clock in to now: today's, or starting
     * within the next two hours.
     *
     * @return Collection<int, Shift>
     */
    public function shiftsToClockInto(User $member): Collection
    {
        return Shift::query()
            ->published()
            ->with('site')
            ->where('user_id', $member->getKey())
            ->where('ends_at', '>', now())
            ->where('starts_at', '<=', now()->addHours(2))
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * The position is used once to work out pass or fail, and is not kept.
     */
    public function clockIn(User $member, ?Shift $shift, ?Site $site, ?float $latitude = null, ?float $longitude = null): ClockRecord
    {
        if ($this->openRecord($member) !== null) {
            throw new RuntimeException('You are already clocked in.');
        }

        if ($shift !== null) {
            if ((int) $shift->user_id !== (int) $member->getKey()) {
                throw new RuntimeException('That shift is not yours.');
            }
            $site = $shift->site;
        }

        $result = $site !== null && $this->geofence->appliesTo($site)
            ? $this->geofence->check($site, $latitude, $longitude)
            : null;

        $record = ClockRecord::query()->create([
            'user_id' => $member->getKey(),
            'shift_id' => $shift?->getKey(),
            'site_id' => $site?->getKey(),
            'clock_in_at' => now(),
            'geofence_result' => $result,
        ]);

        $this->audit->record('operations.clocked_in', $record, array_filter([
            'shift_id' => $record->shift_id,
            'site_id' => $record->site_id,
            'geofence' => $result,
        ]), $member);

        return $record;
    }

    public function startBreak(User $member): void
    {
        $record = $this->requireOpen($member);
        if ($record->openBreak() !== null) {
            throw new RuntimeException('You are already on a break.');
        }

        $record->breaks()->create(['started_at' => now()]);
    }

    public function endBreak(User $member): void
    {
        $break = $this->requireOpen($member)->openBreak();
        if ($break === null) {
            throw new RuntimeException('You are not on a break.');
        }

        $break->update(['ended_at' => now()]);
    }

    public function clockOut(User $member): ClockRecord
    {
        $record = $this->requireOpen($member);
        $record->openBreak()?->update(['ended_at' => now()]);
        $record->update(['clock_out_at' => now()]);

        $this->audit->record('operations.clocked_out', $record, ['minutes' => $record->refresh()->load('breaks')->workedMinutes()], $member);

        return $record;
    }

    /**
     * A manager records the clock-out time for a missed clock-out.
     */
    public function correctClockOut(ClockRecord $record, CarbonImmutable $clockOutAt, User $manager): void
    {
        if ($clockOutAt->lessThanOrEqualTo($record->clock_in_at) || $clockOutAt->isFuture()) {
            throw new RuntimeException('The clock-out time must be after the clock-in time and not in the future.');
        }

        $record->openBreak()?->update(['ended_at' => $clockOutAt]);
        $record->forceFill(['clock_out_at' => $clockOutAt, 'corrected_by' => $manager->getKey()])->save();

        $this->audit->record('operations.clock_out_corrected', $record, [
            'user_id' => $record->user_id,
            'clock_out_at' => $clockOutAt->toIso8601String(),
        ], $manager);
    }

    private function requireOpen(User $member): ClockRecord
    {
        return $this->openRecord($member) ?? throw new RuntimeException('You are not clocked in.');
    }
}
