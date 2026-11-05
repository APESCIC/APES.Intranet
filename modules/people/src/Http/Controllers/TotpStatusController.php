<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\People\Services\CloudronTotpStatus;

class TotpStatusController
{
    public function index(Request $request): View
    {
        abort_unless(
            $request->user()?->can(AccessMatrix::ADMIN_SYSTEM_VIEW)
            || $request->user()?->can(AccessMatrix::HR_ACCESS),
            403,
        );

        $people = User::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'totp_enabled', 'totp_checked_at']);

        return view('people::admin.totp-status', [
            'people' => $people,
            'checkedAt' => $people->max('totp_checked_at'),
        ]);
    }

    public function refresh(Request $request, CloudronTotpStatus $status): RedirectResponse
    {
        abort_unless(
            $request->user()?->can(AccessMatrix::ADMIN_SYSTEM_VIEW)
            || $request->user()?->can(AccessMatrix::HR_ACCESS),
            403,
        );

        $summary = $status->refresh();

        return back()->with('status', sprintf(
            'Refreshed 2FA flags: %d matched, %d with TOTP on.',
            $summary['matched'],
            $summary['enabled'],
        ));
    }
}
