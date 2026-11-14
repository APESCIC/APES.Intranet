<?php

namespace Modules\Hr\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Hr\Models\LeaveBalance;
use Modules\Hr\Models\LeaveRequest;
use RuntimeException;

final class LeaveService
{
    public function balanceFor(User $user, ?int $year = null): LeaveBalance
    {
        $year ??= (int) now()->year;

        return LeaveBalance::query()->firstOrCreate(
            ['user_id' => $user->id, 'year' => $year],
            ['allowance_days' => 25, 'used_days' => 0],
        );
    }

    public function request(User $user, Carbon $starts, Carbon $ends, ?string $reason): LeaveRequest
    {
        $days = $this->countWeekdays($starts, $ends);
        if ($days <= 0) {
            throw new RuntimeException('Leave must cover at least one weekday.');
        }

        $balance = $this->balanceFor($user, (int) $starts->year);
        if ($days > $balance->remainingDays()) {
            throw new RuntimeException('Not enough leave balance remaining.');
        }

        return LeaveRequest::query()->create([
            'user_id' => $user->id,
            'starts_on' => $starts->toDateString(),
            'ends_on' => $ends->toDateString(),
            'days' => $days,
            'status' => LeaveRequest::STATUS_PENDING,
            'reason' => $reason,
        ]);
    }

    public function approve(LeaveRequest $request, User $decider): LeaveRequest
    {
        if ($request->status !== LeaveRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending leave can be approved.');
        }

        $balance = $this->balanceFor($request->user, (int) $request->starts_on->year);
        if ($request->days > $balance->remainingDays()) {
            throw new RuntimeException('Not enough leave balance remaining.');
        }

        $request->fill([
            'status' => LeaveRequest::STATUS_APPROVED,
            'decided_by' => $decider->id,
            'decided_at' => now(),
        ])->save();

        $balance->used_days = (float) $balance->used_days + (float) $request->days;
        $balance->save();

        return $request->refresh();
    }

    public function decline(LeaveRequest $request, User $decider): LeaveRequest
    {
        if ($request->status !== LeaveRequest::STATUS_PENDING) {
            throw new RuntimeException('Only pending leave can be declined.');
        }

        $request->fill([
            'status' => LeaveRequest::STATUS_DECLINED,
            'decided_by' => $decider->id,
            'decided_at' => now(),
        ])->save();

        return $request->refresh();
    }

    public function countWeekdays(Carbon $starts, Carbon $ends): float
    {
        $days = 0.0;
        $cursor = $starts->copy()->startOfDay();
        $last = $ends->copy()->startOfDay();

        while ($cursor->lte($last)) {
            if ($cursor->isWeekday()) {
                $days += 1;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
