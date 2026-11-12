<?php

namespace Modules\Operations\Services;

use Illuminate\Support\Collection;
use Modules\Operations\Models\Availability;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;

/**
 * Flags a shift whose member is unavailable or already on another shift at
 * the same time. A flag is a warning for the rota builder, not a block.
 */
final class ClashDetector
{
    /**
     * @return list<string>
     */
    public function forShift(Shift $shift): array
    {
        if ($shift->user_id === null) {
            return [];
        }

        $clashes = [];

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
