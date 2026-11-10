<?php

namespace Modules\Operations\Console;

use Illuminate\Console\Command;
use Modules\Operations\Services\MissedClockOuts;

class FlagMissedClockOuts extends Command
{
    protected $signature = 'operations:flag-missed-clock-outs';

    protected $description = 'Flag clock records left open after the shift and email the managers';

    public function handle(MissedClockOuts $missed): int
    {
        $this->info('Flagged '.$missed->flag().' missed clock-out(s).');

        return self::SUCCESS;
    }
}
