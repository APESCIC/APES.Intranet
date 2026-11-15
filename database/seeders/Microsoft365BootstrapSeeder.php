<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Microsoft365\Models\MicrosoftLink;

/**
 * Deploy-safe Microsoft 365 link defaults. Runs after permissions seeders.
 * Existing links are left as admins edited them.
 */
class Microsoft365BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(Microsoft365PermissionsSeeder::class);

        $defaults = [
            ['key' => 'outlook', 'name' => 'Outlook', 'url' => 'https://outlook.office.com/mail/', 'icon' => 'mail', 'audience' => 'all', 'sort_order' => 10],
            ['key' => 'onedrive', 'name' => 'OneDrive', 'url' => 'https://www.office.com/launch/onedrive', 'icon' => 'cloud', 'audience' => 'all', 'sort_order' => 20],
            ['key' => 'teams', 'name' => 'Teams', 'url' => 'https://teams.microsoft.com/', 'icon' => 'messages-square', 'audience' => 'all', 'sort_order' => 30],
            ['key' => 'engage', 'name' => 'APES Engage', 'url' => config('communications.engage_url'), 'icon' => 'megaphone', 'audience' => 'all', 'sort_order' => 40],
            ['key' => 'bookings', 'name' => 'Bookings', 'url' => 'https://outlook.office.com/bookings/', 'icon' => 'calendar-check', 'audience' => 'staff', 'sort_order' => 50],
            ['key' => 'loop', 'name' => 'Loop', 'url' => 'https://loop.cloud.microsoft/', 'icon' => 'boxes', 'audience' => 'staff', 'sort_order' => 60],
            ['key' => 'planner', 'name' => 'Planner', 'url' => 'https://planner.cloud.microsoft/', 'icon' => 'list-todo', 'audience' => 'all', 'sort_order' => 70],
            ['key' => 'sharepoint', 'name' => 'SharePoint', 'url' => config('communications.sharepoint_host'), 'icon' => 'folder', 'audience' => 'all', 'sort_order' => 80],
        ];

        foreach ($defaults as $row) {
            MicrosoftLink::query()->firstOrCreate(
                ['key' => $row['key']],
                $row + ['active' => true, 'opens_in_new_tab' => true],
            );
        }
    }
}
