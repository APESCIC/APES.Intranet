<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with fake data only.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Seeders create fake data and only run in the local or testing environment.');
        }

        $this->call([
            RolesAndPermissionsSeeder::class,
            FakeUserSeeder::class,
            QaUserSeeder::class,
        ]);
    }
}
