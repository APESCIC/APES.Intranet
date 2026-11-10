<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Timesheet;
use Modules\Operations\Support\Notifier;
use Modules\Operations\Support\TeamAccess;
use RuntimeException;

/**
 * Weekly timesheets built from finished clock records. A manager approves
 * (which locks it) or returns it with a note.
 */
final class Timesheets
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function build(User $member, CarbonImmutable $anyDayInWeek): Timesheet
    {
        $start = $anyDayInWeek->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();
        $end = $start->addDays(6);

        $timesheet = Timesheet::query()
            ->where('user_id', $member->getKey())
            ->whereDate('period_start', $start)
            ->first();

        if ($timesheet !== null && ! $timesheet->isEditable()) {
            throw new RuntimeException('This timesheet is '.($timesheet->isLocked() ? 'approved and locked' : 'waiting for approval').'.');
        }

        return DB::transaction(function () use ($member, $start, $end, $timesheet) {
            $timesheet ??= Timesheet::query()->create([
                'user_id' => $member->getKey(),
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => Timesheet::STATUS_DRAFT,
            ]);

            $timesheet->entries()->delete();

            $records = ClockRecord::query()
                ->with(['breaks', 'shift', 'site'])
                ->where('user_id', $member->getKey())
                ->whereNotNull('clock_out_at')
                ->where('clock_in_at', '>=', $start)
                ->where('clock_in_at', '<', $end->addDay())
                ->orderBy('clock_in_at')
                ->get();

            foreach ($records as $record) {
                $timesheet->entries()->create([
                    'clock_record_id' => $record->id,
                    'worked_on' => $record->clock_in_at->toDateString(),
                    'minutes' => $record->workedMinutes(),
                    'description' => $record->shift?->role ?? $record->site?->name,
                ]);
            }

            $timesheet->forceFill(['total_minutes' => (int) $timesheet->entries()->sum('minutes')])->save();

            return $timesheet;
        });
    }

    public function submit(Timesheet $timesheet, User $member): void
    {
        if ((int) $timesheet->user_id !== (int) $member->getKey() || ! $timesheet->isEditable()) {
            throw new RuntimeException('This timesheet cannot be submitted.');
        }

        $timesheet->forceFill(['status' => Timesheet::STATUS_SUBMITTED, 'submitted_at' => now()])->save();
        $this->audit->record('operations.timesheet_submitted', $timesheet, ['minutes' => $timesheet->total_minutes], $member);

        Notifier::send(
            TeamAccess::approversFor($member->team, $member),
            'Timesheet to approve: '.$member->name,
            [$member->name.' submitted a timesheet for the week of '.$timesheet->period_start->format('j F Y').': '.$timesheet->hoursLabel().'.'],
            route('operations.timesheets.review'),
        );
    }

    public function review(Timesheet $timesheet, User $manager, bool $approve, ?string $note = null): void
    {
        if ($timesheet->status !== Timesheet::STATUS_SUBMITTED) {
            throw new RuntimeException('Only a submitted timesheet can be approved or returned.');
        }

        if ((int) $timesheet->user_id === (int) $manager->getKey()) {
            throw new RuntimeException('Another manager needs to approve your own timesheet.');
        }

        if (! $approve && blank($note)) {
            throw new RuntimeException('Say why the timesheet is being returned.');
        }

        $timesheet->forceFill([
            'status' => $approve ? Timesheet::STATUS_APPROVED : Timesheet::STATUS_RETURNED,
            'reviewed_by' => $manager->getKey(),
            'reviewed_at' => now(),
            'review_note' => $note,
            'locked_at' => $approve ? now() : null,
        ])->save();

        $this->audit->record('operations.timesheet_'.$timesheet->status, $timesheet, ['minutes' => $timesheet->total_minutes], $manager);

        $timesheet->loadMissing('member');
        Notifier::send(
            [$timesheet->member],
            'Timesheet '.$timesheet->status.': week of '.$timesheet->period_start->format('j F Y'),
            array_values(array_filter([
                'Your timesheet for the week of '.$timesheet->period_start->format('j F Y').' was '.$timesheet->status.' by '.$manager->name.'.',
                $note ? 'Note: '.$note : null,
            ])),
            route('operations.timesheets.show', $timesheet),
        );
    }
}
