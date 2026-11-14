<?php

namespace App\Core\Hr\Contracts;

use Carbon\CarbonInterface;

/**
 * Whether a member has approved leave overlapping a period. Implemented by
 * the HR module; Operations uses this so it never imports HR models.
 */
interface ApprovedLeaveLookup
{
    /**
     * True when the member has approved leave that overlaps the period.
     */
    public function overlaps(int $userId, CarbonInterface $starts, CarbonInterface $ends): bool;

    /**
     * Plain-English leave periods overlapping the window (for clash messages).
     *
     * @return list<string>
     */
    public function overlappingSummaries(int $userId, CarbonInterface $starts, CarbonInterface $ends): array;
}
