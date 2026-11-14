<?php

namespace App\Core\Hr;

use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use Carbon\CarbonInterface;

/**
 * Default when no HR leave module is bound: nobody is on leave.
 */
final class NullApprovedLeaveLookup implements ApprovedLeaveLookup
{
    public function overlaps(int $userId, CarbonInterface $starts, CarbonInterface $ends): bool
    {
        return false;
    }

    public function overlappingSummaries(int $userId, CarbonInterface $starts, CarbonInterface $ends): array
    {
        return [];
    }
}
