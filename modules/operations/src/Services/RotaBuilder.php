<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use RuntimeException;

final class RotaBuilder
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ApprovedLeaveLookup $leave,
    ) {}

    /**
     * Create a draft week for the site with one open shift per active template.
     */
    public function createWeekFromTemplates(Site $site, CarbonImmutable $anyDayInWeek, User $actor): RotaWeek
    {
        $weekStart = $anyDayInWeek->startOfWeek(CarbonImmutable::MONDAY)->startOfDay();

        if (RotaWeek::query()->where('site_id', $site->id)->whereDate('week_start', $weekStart)->exists()) {
            throw new RuntimeException('A rota for this site and week already exists.');
        }

        return DB::transaction(function () use ($site, $weekStart, $actor) {
            $week = RotaWeek::query()->create([
                'site_id' => $site->id,
                'week_start' => $weekStart->toDateString(),
                'status' => RotaWeek::STATUS_DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            $templates = ShiftTemplate::query()
                ->where('site_id', $site->id)
                ->where('active', true)
                ->orderBy('weekday')
                ->orderBy('starts_at')
                ->get();

            foreach ($templates as $template) {
                [$start, $end] = $template->timesInWeek($weekStart);
                $week->shifts()->create([
                    'site_id' => $site->id,
                    'shift_template_id' => $template->id,
                    'role' => $template->role,
                    'starts_at' => $start,
                    'ends_at' => $end,
                ]);
            }

            $this->audit->record('operations.rota_created', $week, [
                'site_id' => $site->id,
                'week_start' => $weekStart->toDateString(),
                'shifts' => $templates->count(),
            ], $actor);

            return $week;
        });
    }

    /**
     * Copy a week's shifts into the following Monday as a draft. People on
     * approved leave for the new dates are left off (shift stays open).
     *
     * @return array{week: RotaWeek, left_off: list<string>}
     */
    public function copyWeek(RotaWeek $source, User $actor): array
    {
        $source->loadMissing(['site', 'shifts.member']);
        $nextStart = CarbonImmutable::parse($source->week_start->toDateString())->addWeek()->startOfDay();

        $existing = RotaWeek::query()
            ->where('site_id', $source->site_id)
            ->whereDate('week_start', $nextStart)
            ->first();

        if ($existing !== null) {
            throw new RuntimeException('A rota for this site and week already exists. Open the existing week instead.');
        }

        return DB::transaction(function () use ($source, $nextStart, $actor) {
            $week = RotaWeek::query()->create([
                'site_id' => $source->site_id,
                'week_start' => $nextStart->toDateString(),
                'status' => RotaWeek::STATUS_DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            $leftOff = [];

            foreach ($source->shifts as $shift) {
                /** @var Shift $shift */
                $startsAt = $shift->starts_at->addWeek();
                $endsAt = $shift->ends_at->addWeek();
                $userId = $shift->user_id;

                if ($userId !== null && $this->leave->overlaps((int) $userId, $startsAt, $endsAt)) {
                    $leftOff[] = ($shift->member->name ?? 'Someone').' ('.$shift->role.' '.$startsAt->format('D j M').')';
                    $userId = null;
                }

                $week->shifts()->create([
                    'site_id' => $source->site_id,
                    'shift_template_id' => $shift->shift_template_id,
                    'role' => $shift->role,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'user_id' => $userId,
                ]);
            }

            $this->audit->record('operations.rota_copied', $week, [
                'site_id' => $source->site_id,
                'from_week_id' => $source->id,
                'week_start' => $nextStart->toDateString(),
                'shifts' => $source->shifts->count(),
                'left_off' => count($leftOff),
            ], $actor);

            return ['week' => $week, 'left_off' => $leftOff];
        });
    }
}
