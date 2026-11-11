<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Helpdesk\Models\Category;
use Modules\Helpdesk\Models\Ticket;

/**
 * Deploy-safe help desk defaults (no fake personal data). Runs on every
 * Cloudron deploy after RolesAndPermissionsSeeder (deploy/cloudron/activate-release.sh).
 * Existing categories are left as admins edited them.
 */
class HelpdeskBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HelpdeskPermissionsSeeder::class);

        $defaults = [
            ['slug' => 'sign-in', 'name' => 'Sign-in or account problem', 'queue' => Ticket::QUEUE_IT, 'default_priority' => 'high', 'sort_order' => 10],
            ['slug' => 'lost-device-or-2fa-reset', 'name' => 'Lost device or 2FA reset', 'queue' => Ticket::QUEUE_IT, 'default_priority' => 'high', 'requires_identity_check' => true, 'sort_order' => 15],
            ['slug' => 'microsoft-365', 'name' => 'Microsoft 365 apps and licences', 'queue' => Ticket::QUEUE_IT, 'default_priority' => 'normal', 'sort_order' => 20],
            ['slug' => 'devices', 'name' => 'Laptop, phone or printer', 'queue' => Ticket::QUEUE_IT, 'default_priority' => 'normal', 'sort_order' => 30],
            ['slug' => 'access-request', 'name' => 'Access to an app or shared folder', 'queue' => Ticket::QUEUE_IT, 'default_priority' => 'low', 'sort_order' => 40],
            ['slug' => 'rota-and-shifts', 'name' => 'Rota, shifts or hours', 'queue' => Ticket::QUEUE_MANAGEMENT, 'default_priority' => 'normal', 'sort_order' => 10],
            ['slug' => 'buildings-and-equipment', 'name' => 'Buildings, vehicles or equipment', 'queue' => Ticket::QUEUE_MANAGEMENT, 'default_priority' => 'normal', 'sort_order' => 20],
            ['slug' => 'concern', 'name' => 'A concern or complaint', 'queue' => Ticket::QUEUE_MANAGEMENT, 'default_priority' => 'high', 'sort_order' => 30],
            ['slug' => 'something-else', 'name' => 'Something else', 'queue' => Ticket::QUEUE_MANAGEMENT, 'default_priority' => 'low', 'sort_order' => 90],
        ];

        foreach ($defaults as $row) {
            Category::query()->firstOrCreate(['slug' => $row['slug']], $row + ['active' => true]);
        }
    }
}
