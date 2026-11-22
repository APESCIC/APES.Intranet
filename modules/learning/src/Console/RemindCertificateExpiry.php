<?php

namespace Modules\Learning\Console;

use Illuminate\Console\Command;
use Modules\Learning\Services\CertificateService;

class RemindCertificateExpiry extends Command
{
    protected $signature = 'learning:remind-certificate-expiry {--days=30 : Days before expiry to remind}';

    protected $description = 'Remind learners when certificates expire soon, then revoke expired ones and re-enrol';

    public function handle(CertificateService $certificates): int
    {
        $days = max(1, (int) $this->option('days'));

        $reminded = $certificates->handleExpiryReminders($days);
        $expired = $certificates->expireAndReenrol();

        $this->info("Reminded {$reminded} certificate(s); expired and re-enrolled {$expired}.");

        return self::SUCCESS;
    }
}
