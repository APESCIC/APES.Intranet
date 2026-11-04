<?php

namespace App\Core\Audit\Console;

use App\Core\Audit\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLog extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Delete audit log entries older than the retention period';

    public function handle(): int
    {
        $days = (int) config('intranet.retention.audit_log_days');
        $deleted = AuditLog::query()->where('occurred_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} audit log entries older than {$days} days.");

        return self::SUCCESS;
    }
}
