<?php

namespace Modules\Operations\Console;

use Illuminate\Console\Command;
use Modules\Operations\Services\Checklists;

class AlertMissedChecklists extends Command
{
    protected $signature = 'operations:alert-missed-checklists';

    protected $description = 'Email managers about checklists not completed by their due time';

    public function handle(Checklists $checklists): int
    {
        $this->info('Alerted '.$checklists->alertMissed().' missed checklist(s).');

        return self::SUCCESS;
    }
}
