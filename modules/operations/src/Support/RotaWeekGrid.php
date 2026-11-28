<?php

namespace Modules\Operations\Support;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;

/**
 * Builds the person-by-day rows for the manager week table.
 */
final class RotaWeekGrid
{
    /**
     * @return array{days: list<CarbonImmutable>, rows: list<array{key: string, label: string, subtitle: ?string, user_id: ?int, cells: array<string, list<Shift>>}>}
     */
    public static function forWeek(RotaWeek $week): array
    {
        $week->loadMissing(['site', 'shifts.member', 'shifts.checklistRuns.template']);

        $weekStart = CarbonImmutable::parse($week->week_start->toDateString())->startOfDay();
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $days[] = $weekStart->addDays($i);
        }

        $teamUsers = User::query()
            ->active()
            ->permission(AccessMatrix::INTRANET_ACCESS)
            ->where('team', $week->site->team)
            ->orderBy('name')
            ->get(['id', 'name', 'team', 'job_title']);

        $assignedIds = $week->shifts
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $extraUsers = User::query()
            ->active()
            ->whereIn('id', $assignedIds->diff($teamUsers->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'team', 'job_title']);

        $people = $teamUsers->concat($extraUsers)->unique('id')->values();

        $byUserAndDay = [];
        $openByDay = [];

        foreach ($week->shifts as $shift) {
            $dayKey = $shift->starts_at->toDateString();
            if ($shift->user_id === null) {
                $openByDay[$dayKey][] = $shift;

                continue;
            }

            $byUserAndDay[$shift->user_id][$dayKey][] = $shift;
        }

        $rows = [];
        foreach ($people as $person) {
            $cells = [];
            foreach ($days as $day) {
                $key = $day->toDateString();
                $cells[$key] = $byUserAndDay[$person->id][$key] ?? [];
            }

            $rows[] = [
                'key' => 'user:'.$person->id,
                'label' => $person->name,
                'subtitle' => $person->primaryRoleLabel() ?: $person->job_title,
                'user_id' => (int) $person->id,
                'cells' => $cells,
            ];
        }

        $openCells = [];
        foreach ($days as $day) {
            $key = $day->toDateString();
            $openCells[$key] = $openByDay[$key] ?? [];
        }

        $rows[] = [
            'key' => 'open',
            'label' => 'Open shifts',
            'subtitle' => 'Unassigned',
            'user_id' => null,
            'cells' => $openCells,
        ];

        return [
            'days' => $days,
            'rows' => $rows,
        ];
    }

    public static function isOvernight(Shift $shift): bool
    {
        return $shift->ends_at->toDateString() !== $shift->starts_at->toDateString();
    }

    /**
     * @param  Collection<int, list<string>>|array<int, list<string>>  $clashes
     * @return list<string>
     */
    public static function flagsFor(Shift $shift, Collection|array $clashes): array
    {
        $flags = [];
        foreach ((is_array($clashes) ? ($clashes[$shift->id] ?? []) : $clashes->get($shift->id, [])) as $clash) {
            $flags[] = $clash;
        }

        return $flags;
    }
}
