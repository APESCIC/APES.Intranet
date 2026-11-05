<?php

namespace App\Core\System\Console;

use App\Core\Access\RoleName;
use App\Core\System\CloudronAddons;
use App\Core\System\Notifications\AddonDown;
use App\Core\System\SystemCheck;
use App\Core\System\SystemStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class RunSystemChecks extends Command
{
    /** Failures in a row before admins are emailed. */
    public const ALERT_AFTER = 3;

    protected $signature = 'system:check';

    protected $description = 'Check the Cloudron addons, store the results and email admins about a new outage';

    public function handle(SystemStatus $status): int
    {
        $results = $status->run();
        $status->recordSchedulerHeartbeat();

        $newOutages = array_filter(
            $results,
            fn (SystemCheck $result) => $result->status === SystemCheck::DOWN
                && $result->consecutive_failures >= self::ALERT_AFTER
                && $result->alerted_at === null,
        );

        if ($newOutages !== []) {
            $labels = array_map(fn (string $key) => CloudronAddons::definitions()[$key]['label'], array_keys($newOutages));
            Notification::send(User::role(RoleName::Admin->value)->get(), new AddonDown($labels));

            foreach ($newOutages as $result) {
                $result->forceFill(['alerted_at' => now()])->save();
            }
        }

        foreach ($results as $key => $result) {
            $this->line("{$key}: {$result->status}");
        }

        return self::SUCCESS;
    }
}
