<?php

namespace Modules\People\Console;

use Illuminate\Console\Command;
use Modules\People\Services\MemberDirectorySync;

class SyncDirectory extends Command
{
    protected $signature = 'people:sync-directory';

    protected $description = 'Read Cloudron LDAP and create, update or deactivate intranet members.';

    public function handle(MemberDirectorySync $sync): int
    {
        $summary = $sync->run('ldap');

        $this->info(sprintf(
            'Directory sync finished: %d seen, %d created, %d updated, %d deactivated, %d unchanged.',
            $summary['seen'],
            $summary['created'],
            $summary['updated'],
            $summary['deactivated'],
            $summary['unchanged'],
        ));

        return self::SUCCESS;
    }
}
