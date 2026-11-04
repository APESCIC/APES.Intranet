<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FakeUserSeeder extends Seeder
{
    public const COUNT = 10;

    /**
     * Create fake people with example.test addresses. Safe to run again.
     */
    public function run(): void
    {
        for ($i = 1; $i <= self::COUNT; $i++) {
            $email = sprintf('person%02d@example.test', $i);

            if (User::query()->where('email', $email)->exists()) {
                continue;
            }

            User::factory()->create(['email' => $email]);
        }
    }
}
