<?php

namespace App\Core\Search;

use App\Core\Modules\ModuleRegistry;
use App\Models\User;

/**
 * Resolves tagged search providers, skipping those for disabled modules.
 */
final class SearchProviders
{
    public function __construct(private readonly ModuleRegistry $modules) {}

    /**
     * @return list<array{title: string, url: string, snippet?: string}>
     */
    public function search(User $user, string $query): array
    {
        $results = [];

        foreach (app()->tagged('intranet.search') as $provider) {
            if (! $provider instanceof SearchProvider) {
                continue;
            }

            if (! $this->modules->enabled($provider->moduleSlug())) {
                continue;
            }

            array_push($results, ...$provider->search($user, $query));
        }

        return $results;
    }
}
