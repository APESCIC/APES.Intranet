<?php

namespace Database\Seeders;

use App\Core\Qa\QaLogin;
use Illuminate\Database\Seeder;

class QaUserSeeder extends Seeder
{
    /**
     * One fake account per approved group and per common stack, for the
     * local QA role switcher.
     */
    public function run(): void
    {
        foreach (array_keys(QaLogin::profiles()) as $profile) {
            QaLogin::userFor($profile);
        }
    }
}
