<?php

namespace App\Core\Changelog;

final readonly class ChangelogSyncResult
{
    /**
     * @param  list<string>  $created
     * @param  list<string>  $updated
     * @param  list<string>  $unchanged
     * @param  list<string>  $orphaned  Versions in the database with no file any more.
     */
    public function __construct(
        public array $created,
        public array $updated,
        public array $unchanged,
        public array $orphaned,
    ) {}
}
