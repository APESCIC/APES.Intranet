<?php

namespace Modules\People\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Line-manager links: no self-management and no loops.
 */
final class ManagerGraph
{
    public static function assertCanReportTo(User $member, ?User $manager): void
    {
        if ($manager === null) {
            return;
        }

        if ($member->is($manager)) {
            throw new InvalidArgumentException('A person cannot be their own manager.');
        }

        $walker = $manager;
        $seen = [(int) $member->getKey()];

        while ($walker !== null) {
            $id = (int) $walker->getKey();

            if (in_array($id, $seen, true)) {
                throw new InvalidArgumentException('That manager link would create a loop.');
            }

            $seen[] = $id;
            $walker = $walker->manager;
        }
    }

    /**
     * Active people with no manager, each with nested reports.
     *
     * @return Collection<int, array{person: User, reports: Collection}>
     */
    public static function tree(): Collection
    {
        $people = User::query()
            ->active()
            ->with(['manager', 'directReports' => fn ($q) => $q->active()->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $roots = $people->filter(function (User $person) use ($people) {
            return $person->manager_id === null || ! $people->has($person->manager_id);
        });

        return $roots->map(fn (User $person) => self::node($person, $people))->values();
    }

    /**
     * @param  Collection<int, User>  $people
     * @return array{person: User, reports: Collection}
     */
    private static function node(User $person, Collection $people): array
    {
        $reports = $people
            ->where('manager_id', $person->getKey())
            ->map(fn (User $report) => self::node($report, $people))
            ->values();

        return ['person' => $person, 'reports' => $reports];
    }
}
