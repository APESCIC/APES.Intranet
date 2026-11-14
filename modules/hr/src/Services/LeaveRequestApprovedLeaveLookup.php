<?php

namespace Modules\Hr\Services;

use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use Carbon\CarbonInterface;
use Modules\Hr\Models\LeaveRequest;

final class LeaveRequestApprovedLeaveLookup implements ApprovedLeaveLookup
{
    public function overlaps(int $userId, CarbonInterface $starts, CarbonInterface $ends): bool
    {
        return LeaveRequest::query()
            ->approvedCovering($userId, $starts, $ends)
            ->exists();
    }

    public function overlappingSummaries(int $userId, CarbonInterface $starts, CarbonInterface $ends): array
    {
        return LeaveRequest::query()
            ->approvedCovering($userId, $starts, $ends)
            ->orderBy('starts_on')
            ->get()
            ->map(fn (LeaveRequest $leave) => 'On approved leave '.$leave->starts_on->format('D j M')
                .'–'.$leave->ends_on->format('D j M'))
            ->all();
    }
}
