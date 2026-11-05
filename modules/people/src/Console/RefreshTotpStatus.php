<?php

namespace Modules\People\Console;

use App\Core\Audit\AuditLogger;
use Illuminate\Console\Command;
use Modules\People\Services\CloudronTotpStatus;

class RefreshTotpStatus extends Command
{
    protected $signature = 'people:refresh-totp-status';

    protected $description = 'Read Cloudron totpEnabled flags into the intranet (yes/no only).';

    public function handle(CloudronTotpStatus $status, AuditLogger $audit): int
    {
        $summary = $status->refresh();
        $audit->record('people.totp_status_refreshed', null, $summary);
        $this->info(sprintf(
            '2FA status refresh: %d checked, %d matched, %d enabled, %d missing.',
            $summary['checked'],
            $summary['matched'],
            $summary['enabled'],
            $summary['missing'],
        ));

        return self::SUCCESS;
    }
}
