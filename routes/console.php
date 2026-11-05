<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('system:check')->everyFiveMinutes()->withoutOverlapping();
