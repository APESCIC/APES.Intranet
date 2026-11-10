<?php

namespace Modules\Operations\Services;

use App\Core\Access\RoleName;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Operations\Models\Timesheet;

/**
 * Approved volunteer hours per person for a date range. The screen and the
 * CSV export both use these rows, so their totals always match.
 */
final class VolunteerHours
{
    /**
     * @return Collection<int, array{user: User, timesheets: int, minutes: int}>
     */
    public function totals(CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $volunteerIds = User::query()->role(RoleName::Volunteer->value)->pluck('id');

        return Timesheet::query()
            ->with('member')
            ->where('status', Timesheet::STATUS_APPROVED)
            ->whereIn('user_id', $volunteerIds)
            ->whereDate('period_start', '>=', $from->startOfWeek(CarbonImmutable::MONDAY))
            ->whereDate('period_start', '<=', $to)
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $sheets) => [
                'user' => $sheets->first()->member,
                'timesheets' => $sheets->count(),
                'minutes' => (int) $sheets->sum('total_minutes'),
            ])
            ->sortBy(fn (array $row) => $row['user']->name)
            ->values();
    }

    /**
     * @param  Collection<int, array{user: User, timesheets: int, minutes: int}>  $rows
     */
    public function csv(Collection $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Name', 'Email', 'Team', 'Approved timesheets', 'Minutes', 'Hours'], escape: '');

        foreach ($rows as $row) {
            fputcsv($handle, [
                $this->safe($row['user']->name),
                $this->safe($row['user']->email),
                $this->safe((string) $row['user']->team),
                $row['timesheets'],
                $row['minutes'],
                self::hours($row['minutes']),
            ], escape: '');
        }

        fputcsv($handle, ['Total', '', '', $rows->sum('timesheets'), $rows->sum('minutes'), self::hours((int) $rows->sum('minutes'))], escape: '');

        rewind($handle);

        return (string) stream_get_contents($handle);
    }

    public static function hours(int $minutes): string
    {
        return number_format($minutes / 60, 2, '.', '');
    }

    /**
     * Stops spreadsheet formula injection from names typed by members.
     */
    private function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
