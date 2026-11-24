<?php

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('03:15');
Schedule::command('analytics:record-daily')->dailyAt('03:30');
Schedule::command('system:check')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('access-review:remind')->quarterlyOn(1, '09:00');

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
