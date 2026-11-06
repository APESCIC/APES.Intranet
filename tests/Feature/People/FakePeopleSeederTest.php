<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FakeUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds at least fifty fake members across account types', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBeGreaterThanOrEqual(50)
        ->and(User::role('staff')->count())->toBeGreaterThan(0)
        ->and(User::role('volunteer')->count())->toBeGreaterThan(0)
        ->and(User::role('student')->count())->toBeGreaterThan(0)
        ->and(FakeUserSeeder::COUNT)->toBeGreaterThanOrEqual(50);
});
