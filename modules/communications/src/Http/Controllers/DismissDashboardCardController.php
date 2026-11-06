<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Communications\Models\DashboardDismissal;

class DismissDashboardCardController
{
    public function __invoke(Request $request, string $card): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);
        abort_unless($card === 'two-factor-nudge', 404);

        $days = (int) config('communications.two_factor_nudge.dismiss_days', 14);

        DashboardDismissal::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'card_key' => $card],
            ['dismissed_until' => now()->addDays($days)],
        );

        return back();
    }
}
