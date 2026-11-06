<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Communications\Models\PolicyDocument;

/**
 * Fake communications extras for local QA only.
 */
class CommunicationsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CommunicationsBootstrapSeeder::class);

        PolicyDocument::query()->updateOrCreate(
            ['title' => 'Sample code of conduct (fake)'],
            [
                'sharepoint_url' => rtrim((string) config('communications.sharepoint_host'), '/').'/sites/intranet/Shared%20Documents/code-of-conduct',
                'owner_name' => 'People team (fake)',
                'review_due_on' => now()->addMonths(6)->toDateString(),
                'active' => true,
            ],
        );
    }
}
