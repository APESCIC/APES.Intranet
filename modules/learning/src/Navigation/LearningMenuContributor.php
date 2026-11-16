<?php

namespace Modules\Learning\Navigation;

use App\Core\Modules\BelongsToModule;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Modules\Learning\LearningPermissions;

/**
 * Staff menu stub for Learning. Kept separate from HR “My training”.
 */
final class LearningMenuContributor implements BelongsToModule, MenuContributor
{
    public function moduleSlug(): string
    {
        return 'learning';
    }

    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode === MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('learning', 'Learning', [
                NavItem::route(
                    'learning-mine',
                    'My learning',
                    'book-open',
                    LearningPermissions::ACCESS,
                    'learning.mine',
                    'learning.mine',
                    'learning',
                ),
            ]),
        ];
    }
}
