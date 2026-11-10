<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use RuntimeException;

final class RotaBuilder
{
    public function __construct(private readonly AuditLogger $audit) {}

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
}
