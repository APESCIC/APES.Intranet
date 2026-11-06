<?php

namespace Modules\Communications\Console;

use Illuminate\Console\Command;
use Modules\Communications\Http\Controllers\EventController;

class SendEventReminders extends Command
{
    protected $signature = 'comms:send-event-reminders';

    protected $description = 'Send due event reminder emails';

    public function handle(EventController $events): int
    {
        $count = $events->sendDueReminders();
        $this->info("Sent reminders for {$count} event(s).");

        return self::SUCCESS;
    }
}
