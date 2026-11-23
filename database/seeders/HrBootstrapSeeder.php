<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Cloudron deploy after RolesAndPermissionsSeeder (deploy/cloudron/activate-release.sh).
 */
class HrBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HrPermissionsSeeder::class);
    }
}
