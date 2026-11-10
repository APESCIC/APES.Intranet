<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Deploy-safe operations defaults (no fake personal data). Runs on every
 * Cloudron deploy after RolesAndPermissionsSeeder (deploy/cloudron/activate-release.sh).
 */
class OperationsBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OperationsPermissionsSeeder::class);
    }
}
