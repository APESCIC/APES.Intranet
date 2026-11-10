<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('system:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('access-review:remind')->quarterlyOn(1, '09:00');
Schedule::command('people:sync-directory')->dailyAt('02:15')->withoutOverlapping();
Schedule::command('people:refresh-totp-status')->dailyAt('02:30')->withoutOverlapping();
Schedule::command('comms:send-event-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('operations:flag-missed-clock-outs')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('operations:alert-missed-checklists')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('demo:seed', function () {
    if (! app()->environment('local')) {
        $this->error(DemoSeeder::REFUSAL);

        return 1;
    }

    return $this->call('db:seed', ['--class' => DemoSeeder::class]);
})->purpose('Load the fake demo data (local only)');

Artisan::command('demo:reset', function () {
    if (! app()->environment('local')) {
        $this->error(DemoSeeder::REFUSAL.' Nothing was reset.');

        return 1;
    }

    if ($this->call('migrate:fresh') !== 0) {
        return 1;
    }

    return $this->call('db:seed', ['--class' => DemoSeeder::class]);
})->purpose('Empty the local database, migrate it and load the fake demo data (local only)');
