<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('system:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('access-review:remind')->quarterlyOn(1, '09:00');
