<?php

namespace Modules\Operations\Dashboard;

use App\Core\Access\AccessMatrix;
use App\Core\AdminDashboard\AdminDashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Models\User;
use Modules\Operations\Models\ClockRecord;
use Modules\Operations\Models\Shift;
use Modules\Operations\OperationsPermissions;

final class OperationsAdminDashboardCards implements AdminDashboardCardContributor, BelongsToModule
{
    public function moduleSlug(): string
    {
        return 'operations';
    }

    public function cards(User $user): array
    {
        $cards = [];

        if ($user->can(AccessMatrix::INTRANET_ACCESS)) {
            $clockedIn = ClockRecord::query()->whereNull('clock_out_at')->count();
            $cards[] = [
                'key' => 'operations-admin-clocked-in',
                'title' => 'Clocked in now',
                'body' => $clockedIn === 0
                    ? 'Nobody is clocked in right now.'
                    : trans_choice(':count person is clocked in.|:count people are clocked in.', $clockedIn),
                'href' => route('operations.clock.team'),
                'permission' => AccessMatrix::INTRANET_ACCESS,
                'module' => 'operations',
                'icon' => 'clock',
                'group' => 'operations',
                'group_heading' => 'Operations',
            ];
        }

        if ($user->can(OperationsPermissions::ROTA_MANAGE)) {
            $todayShifts = Shift::query()
                ->published()
                ->whereDate('starts_at', now()->toDateString())
                ->get();
            $open = $todayShifts->filter(fn (Shift $shift) => $shift->isOpen())->count();
            $total = $todayShifts->count();

            $body = $total === 0
                ? 'No published shifts for today.'
                : $total.' published today'
                    .($open > 0 ? ', '.$open.' still open.' : ', all covered.');

            $cards[] = [
                'key' => 'operations-admin-rota-cover',
                'title' => 'Rota cover today',
                'body' => $body,
                'href' => route('operations.rota.index'),
                'permission' => OperationsPermissions::ROTA_MANAGE,
                'module' => 'operations',
                'icon' => 'calendar-days',
                'group' => 'operations',
                'group_heading' => 'Operations',
                'tone' => $open > 0 ? 'warn' : null,
            ];
        }

        return $cards;
    }
}
