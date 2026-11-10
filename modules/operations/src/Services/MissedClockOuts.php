<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Support\Notifier;
use Modules\Operations\Support\TeamAccess;

/**
 * Flags clock records still open well after their shift ended (or, with no
 * shift, after a long time) and tells the member's managers.
 */
final class MissedClockOuts
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function flag(): int
    {
        $grace = (int) config('operations.clock.missed_clock_out_grace_minutes');
        $unscheduled = (int) config('operations.clock.missed_clock_out_unscheduled_hours');

        $records = ClockRecord::query()
            ->with(['member', 'shift', 'site'])
            ->whereNull('clock_out_at')
            ->whereNull('missed_clock_out_flagged_at')
            ->where(function (Builder $query) use ($grace, $unscheduled) {
                $query->whereHas('shift', fn (Builder $shift) => $shift->where('ends_at', '<', now()->subMinutes($grace)))
                    ->orWhere(fn (Builder $none) => $none->whereNull('shift_id')->where('clock_in_at', '<', now()->subHours($unscheduled)));
            })
            ->get();

        foreach ($records as $record) {
            $record->forceFill(['missed_clock_out_flagged_at' => now()])->save();
            $this->audit->record('operations.missed_clock_out_flagged', $record, ['user_id' => $record->user_id]);

            $member = $record->member;
            Notifier::send(
                TeamAccess::approversFor($record->site->team ?? $member?->team, $member),
                'Missed clock-out: '.($member->name ?? 'a member'),
                [
                    ($member->name ?? 'A member').' clocked in at '.$record->clock_in_at->format('D j M H:i')
                        .($record->shift ? ' for '.$record->shift->label() : '').' and has not clocked out.',
                    'Please record their clock-out time on the team clock page.',
                ],
                route('operations.clock.team'),
            );
        }

        return $records->count();
    }
}
