<?php

namespace App\Core\AdminDashboard;

use App\Core\Access\AccessMatrix;
use App\Core\Analytics\AnalyticsDailyTotal;
use App\Core\Analytics\RecordDailyAnalytics;
use App\Core\Audit\AuditLog;
use App\Core\Modules\ModuleRegistry;
use App\Core\Version\AppVersion;
use App\Models\User;
/**
 * Core admin dashboard cards that do not belong to a feature module.
 */
final class CoreAdminCards implements AdminDashboardCardContributor
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly AppVersion $version,
    ) {}

    public function cards(User $user): array
    {
        return array_values(array_filter([
            $this->signInsCard($user),
            $this->moduleHealthCard($user),
            $this->recentActivityCard($user),
            $this->versionsCard($user),
        ]));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function signInsCard(User $user): ?array
    {
        if (! $user->can(AccessMatrix::ADMIN_SYSTEM_VIEW)) {
            return null;
        }

        $from = now()->subDays(13)->startOfDay();
        $rows = AnalyticsDailyTotal::query()
            ->where('metric', RecordDailyAnalytics::METRIC_SIGN_INS)
            ->where('metric_date', '>=', $from->toDateString())
            ->orderBy('metric_date')
            ->get(['metric_date', 'count']);

        $byDate = $rows->mapWithKeys(fn (AnalyticsDailyTotal $row) => [
            $row->metric_date->toDateString() => (int) $row->count,
        ]);

        $points = [];
        $total = 0;
        for ($i = 0; $i < 14; $i++) {
            $day = $from->copy()->addDays($i);
            $count = (int) ($byDate[$day->toDateString()] ?? 0);
            $total += $count;
            $points[] = ['label' => $day->format('j M'), 'count' => $count];
        }

        $body = $total === 0
            ? 'No sign-ins recorded in the last 14 days yet.'
            : $total.' sign-ins in the last 14 days.';

        return [
            'key' => 'core-sign-ins',
            'title' => 'Sign-ins, last 14 days',
            'body' => $body,
            'href' => route('admin.audit.index'),
            'permission' => AccessMatrix::ADMIN_SYSTEM_VIEW,
            'icon' => 'log-in',
            'group' => 'system',
            'group_heading' => 'System',
            'chart' => $this->barChartSvg($points),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function moduleHealthCard(User $user): ?array
    {
        if (! $user->can(AccessMatrix::ADMIN_MODULES_MANAGE)) {
            return null;
        }

        $manifests = $this->modules->manifests();
        $enabled = $manifests->filter(fn ($m, $slug) => $this->modules->enabled((string) $slug))->count();
        $total = $manifests->count();
        $disabled = $total - $enabled;

        $body = $total === 0
            ? 'No modules are registered.'
            : $enabled.' on, '.$disabled.' off.';

        return [
            'key' => 'core-module-health',
            'title' => 'Module health',
            'body' => $body,
            'href' => route('admin.modules.index'),
            'permission' => AccessMatrix::ADMIN_MODULES_MANAGE,
            'icon' => 'blocks',
            'group' => 'system',
            'group_heading' => 'System',
            'tone' => $disabled > 0 ? 'warn' : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function recentActivityCard(User $user): ?array
    {
        if (! $user->can(AccessMatrix::ADMIN_AUDIT_VIEW)) {
            return null;
        }

        $latest = AuditLog::query()->latest('occurred_at')->limit(5)->get();
        if ($latest->isEmpty()) {
            return [
                'key' => 'core-recent-activity',
                'title' => 'Recent admin activity',
                'body' => 'No audit events yet.',
                'href' => route('admin.audit.index'),
                'permission' => AccessMatrix::ADMIN_AUDIT_VIEW,
                'icon' => 'scroll-text',
                'group' => 'system',
                'group_heading' => 'System',
            ];
        }

        $lines = $latest->map(function (AuditLog $entry) {
            $when = $entry->occurred_at->timezone('Europe/London')->format('j M H:i');

            return $when.' · '.$entry->event;
        })->implode("\n");

        return [
            'key' => 'core-recent-activity',
            'title' => 'Recent admin activity',
            'body' => $lines,
            'href' => route('admin.audit.index'),
            'permission' => AccessMatrix::ADMIN_AUDIT_VIEW,
            'icon' => 'scroll-text',
            'group' => 'system',
            'group_heading' => 'System',
            'preserve_newlines' => true,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function versionsCard(User $user): ?array
    {
        if (! $user->can(AccessMatrix::ADMIN_SYSTEM_VIEW)) {
            return null;
        }

        $lines = ['Core '.$this->version->label()];
        foreach ($this->modules->manifests()->sortKeys() as $slug => $manifest) {
            $lines[] = $slug.' '.$manifest->version;
        }

        return [
            'key' => 'core-versions',
            'title' => 'Versions',
            'body' => implode("\n", $lines),
            'href' => route('admin.system.index'),
            'permission' => AccessMatrix::ADMIN_SYSTEM_VIEW,
            'icon' => 'tag',
            'group' => 'system',
            'group_heading' => 'System',
            'preserve_newlines' => true,
        ];
    }

    /**
     * @param  list<array{label: string, count: int}>  $points
     */
    private function barChartSvg(array $points): string
    {
        $width = 280;
        $height = 64;
        $max = max(1, ...array_column($points, 'count'));
        $barWidth = (int) floor($width / max(1, count($points)));
        $bars = '';

        foreach ($points as $i => $point) {
            $h = (int) round(($point['count'] / $max) * ($height - 4));
            $x = $i * $barWidth + 2;
            $y = $height - $h;
            $label = e($point['label']);
            $count = (int) $point['count'];
            $bars .= '<rect x="'.$x.'" y="'.$y.'" width="'.max(2, $barWidth - 4).'" height="'.$h.'" rx="2" fill="currentColor" opacity="0.7">'
                .'<title>'.$label.': '.$count.'</title></rect>';
        }

        return '<svg class="sg-admin-dashboard-chart" viewBox="0 0 '.$width.' '.$height.'" width="100%" height="64" role="img" aria-label="Sign-ins over the last 14 days">'.$bars.'</svg>';
    }
}
