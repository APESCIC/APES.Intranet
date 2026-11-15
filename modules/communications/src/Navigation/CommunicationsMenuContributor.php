<?php

namespace Modules\Communications\Navigation;

use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Models\User;

/**
 * Microsoft 365 nav moved to modules/microsoft-365. This contributor stays
 * registered so Communications can add groups again later without rewiring.
 */
final class CommunicationsMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'communications';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        return [];
    }
}
