<?php

namespace Modules\People\Console;

use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryShadowComparison;
use App\Core\Auth\DirectorySource;
use Illuminate\Console\Command;
use Modules\People\Services\MemberDirectorySync;
use Throwable;

class SyncDirectory extends Command
{
    protected $signature = 'people:sync-directory';

    protected $description = 'Read the configured directory and create, update or deactivate intranet members.';

    public function handle(MemberDirectorySync $sync, DirectoryShadowComparison $shadow): int
    {
        $source = DirectorySource::syncAuditSource();
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

        if (DirectorySource::isCloudron() && DirectorySource::ldapConfigured()) {
            try {
                $comparison = $shadow->run(app(DirectoryRoster::class));
                $this->info(sprintf(
                    'LDAP shadow comparison: %s (%d difference(s)).',
                    $comparison['status'],
                    $comparison['difference_count'],
                ));
            } catch (Throwable $exception) {
                report($exception);
                $this->warn('LDAP shadow comparison failed; sync itself succeeded.');
            }
        }

        return self::SUCCESS;
    }
}
