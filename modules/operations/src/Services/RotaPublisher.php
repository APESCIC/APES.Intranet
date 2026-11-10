<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Support\Notifier;

final class RotaPublisher
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function publish(RotaWeek $week, User $actor): void
    {
        $week->forceFill([
            'status' => RotaWeek::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $actor->getKey(),
        ])->save();

        $week->loadMissing('site');
        $shifts = $week->shifts()->with('member')->get();

        $this->audit->record('operations.rota_published', $week, [
            'site_id' => $week->site_id,
            'week_start' => $week->week_start->toDateString(),
            'shifts' => $shifts->count(),
            'open_shifts' => $shifts->whereNull('user_id')->count(),
        ], $actor);

        foreach ($shifts->whereNotNull('user_id')->groupBy('user_id') as $memberShifts) {
            /** @var Shift $first */
            $first = $memberShifts->first();
            $lines = ['Your rota for '.$week->site->name.', week of '.$week->week_start->format('j F Y').', is published.'];
            foreach ($memberShifts as $shift) {
                $lines[] = '- '.$shift->label();
            }

            Notifier::send([$first->member], 'Rota published: '.$week->site->name, $lines, route('operations.rota.index'));
        }
    }
}
