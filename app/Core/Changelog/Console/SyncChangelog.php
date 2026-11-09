<?php

namespace App\Core\Changelog\Console;

use App\Core\Changelog\ReleaseChangelogSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncChangelog extends Command
{
    protected $signature = 'changelog:sync';

    protected $description = 'Copy the release notes in changelog/releases into the change log hub';

    public function handle(ReleaseChangelogSync $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (Throwable $exception) {
            Log::error('changelog:sync failed; the change log hub keeps its previous entries.', ['error' => $exception->getMessage()]);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Release notes synced: %d created, %d updated, %d unchanged.',
            count($result->created),
            count($result->updated),
            count($result->unchanged),
        ));
        if ($result->orphaned !== []) {
            $this->warn('In the database but no longer in changelog/releases (kept, not deleted): '.implode(', ', $result->orphaned));
        }

        return self::SUCCESS;
    }
}
