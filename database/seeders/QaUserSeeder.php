<?php

namespace Database\Seeders;

use App\Core\Access\RoleName;
use App\Core\Qa\QaLogin;
use Illuminate\Database\Seeder;

class QaUserSeeder extends Seeder
{
    /**
     * One fake account per role for the local QA role switcher.
     */
    public function run(): void
    {
        foreach (RoleName::cases() as $role) {
            QaLogin::userFor($role);
        }
    }
}
