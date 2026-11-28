<?php

namespace Modules\People\Console;

use Illuminate\Console\Command;
use Modules\People\Services\MemberDirectorySync;

class SyncDirectory extends Command
{
    protected $signature = 'people:sync-directory';

    protected $description = 'Read the Cloudron directory and create, update or deactivate intranet members.';

    public function handle(MemberDirectorySync $sync): int
    {
        $source = 'cloudron_api';
        $summary = $sync->run($source);

        if (isset($summary['stopped'])) {
            $this->error(sprintf('Directory sync stopped (%s). No member records were changed.', $summary['stopped']));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Directory sync finished (%s): %d seen, %d created, %d updated, %d deactivated, %d unchanged.',
            $source,
            $summary['seen'],
            $summary['created'],
            $summary['updated'],
            $summary['deactivated'],
            $summary['unchanged'],
        ));

        return self::SUCCESS;
    }
}
