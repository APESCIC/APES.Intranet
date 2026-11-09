<?php

namespace Database\Seeders;

use App\Core\Changelog\ReleaseChangelogSync;
use Illuminate\Database\Seeder;

/**
 * Loads the reviewed release notes from changelog/releases, as a deploy does,
 * so the change log hub has content in local and CI databases.
 */
class ReleaseNotesSeeder extends Seeder
{
    public function run(ReleaseChangelogSync $sync): void
    {
        $sync->sync();
    }
}
