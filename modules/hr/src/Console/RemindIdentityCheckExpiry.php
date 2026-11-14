<?php

namespace Modules\Hr\Console;

use App\Core\Access\AccessMatrix;
use App\Core\Notifications\AlertDispatcher;
use App\Core\Notifications\NotificationCategory;
use App\Models\User;
use Illuminate\Console\Command;
use Modules\Hr\Models\IdentityCheck;

class RemindIdentityCheckExpiry extends Command
{
    protected $signature = 'hr:remind-identity-check-expiry {--days=30 : Days before expiry to remind}';

    protected $description = 'Remind HR when DBS or right-to-work checks expire within the window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $today = now()->startOfDay();
        $until = $today->copy()->addDays($days);

        $due = IdentityCheck::query()
            ->with('user')
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '>=', $today->toDateString())
            ->whereDate('expires_on', '<=', $until->toDateString())
            ->where(function ($query) use ($today) {
                $query->whereNull('expiry_reminded_at')
                    ->orWhereDate('expiry_reminded_at', '<', $today->toDateString());
            })
            ->get();

        if ($due->isEmpty()) {
            $this->info('No identity checks need an expiry reminder.');

            return self::SUCCESS;
        }

        $reviewers = User::permission(AccessMatrix::HR_SPECIAL_CATEGORY_VIEW)->get();
        AlertDispatcher::send(
            $reviewers,
            NotificationCategory::AccountSecurity,
            'You have an HR check reminder on the APES Intranet.',
            'Open the intranet to view expiring DBS or right-to-work checks.',
            route('hr.checks.report'),
        );

        foreach ($due as $check) {
            $check->forceFill(['expiry_reminded_at' => now()])->save();
        }

        $this->info("Reminded {$reviewers->count()} HR viewers about {$due->count()} check(s).");

        return self::SUCCESS;
    }
}
