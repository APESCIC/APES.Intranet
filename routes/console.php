<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('system:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('access-review:remind')->quarterlyOn(1, '09:00');
Schedule::command('people:sync-directory')->dailyAt('02:15')->withoutOverlapping();
Schedule::command('people:refresh-totp-status')->dailyAt('02:30')->withoutOverlapping();
