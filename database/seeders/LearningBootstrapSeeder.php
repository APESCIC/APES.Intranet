<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Deploy-safe Learning defaults. Runs after core roles exist.
 */
class LearningBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LearningPermissionsSeeder::class);
    }
}
