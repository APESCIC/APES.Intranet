<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\FakeUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds fake people with example.test addresses only', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(FakeUserSeeder::COUNT + count(RoleName::cases()))
        ->and(User::query()->where('email', 'not like', '%@example.test')->count())->toBe(0);
});

it('can run the seeders twice without duplicating people', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(FakeUserSeeder::COUNT + count(RoleName::cases()));
});

it('refuses to seed outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    app(DatabaseSeeder::class)->run();
})->throws(RuntimeException::class);
