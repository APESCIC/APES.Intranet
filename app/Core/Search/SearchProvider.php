<?php

namespace App\Core\Search;

use App\Core\Modules\BelongsToModule;
use App\Models\User;

/**
 * Optional search contribution from a module. Tagged `intranet.search`.
 * Core skips providers whose module is disabled.
 */
interface SearchProvider extends BelongsToModule
{
    /**
     * @return list<array{title: string, url: string, snippet?: string}>
     */
    public function search(User $user, string $query): array;
}
