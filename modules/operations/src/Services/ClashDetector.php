<?php

namespace Modules\Operations\Services;

use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use Illuminate\Support\Collection;
use Modules\Operations\Models\Availability;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;

/**
 * Flags a shift whose member is unavailable, on approved leave, or already on
 * another shift at the same time. Leave is also a hard block on assignment.
 */
final class ClashDetector
{
    public function __construct(private readonly ApprovedLeaveLookup $leave) {}

    /**
     * @return list<string>
     */
    public function forShift(Shift $shift): array
    {
        if ($shift->user_id === null) {
            return [];
        }

        $clashes = [];

        foreach ($this->leave->overlappingSummaries((int) $shift->user_id, $shift->starts_at, $shift->ends_at) as $summary) {
            $clashes[] = $summary;
        }

        $unavailable = Availability::query()
            ->where('user_id', $shift->user_id)
            ->where('starts_at', '<', $shift->ends_at)
            ->where('ends_at', '>', $shift->starts_at)
            ->orderBy('starts_at')
            ->get();

        foreach ($unavailable as $period) {
            $clashes[] = 'Unavailable '.$period->starts_at->format('D j M H:i').'–'.$period->ends_at->format('D j M H:i')
                .($period->note ? ' ('.$period->note.')' : '');
        }

        $overlapping = Shift::query()
            ->with('site')
            ->where('user_id', $shift->user_id)
            ->whereKeyNot($shift->getKey())
            ->where('starts_at', '<', $shift->ends_at)
            ->where('ends_at', '>', $shift->starts_at)
            ->get();

        foreach ($overlapping as $other) {
            $clashes[] = 'Also on '.$other->label().' at '.($other->site->name ?? 'another site');
        }

        return $clashes;
    }

    /**
     * @return Collection<int, non-empty-list<string>> Shift id => clashes, only shifts that clash.
     */
    public function forWeek(RotaWeek $week): Collection
    {
        return $week->shifts()->get()
            ->mapWithKeys(fn (Shift $shift) => [$shift->id => $this->forShift($shift)])
            ->filter(fn (array $clashes) => $clashes !== []);
    }
}
