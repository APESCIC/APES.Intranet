<?php

namespace App\Core\Legal\Navigation;

use App\Core\Access\AccessMatrix;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;

/**
 * Admin › Apps and content › Legal. Routes arrive in later v0.6.18 stories;
 * until then the entries show as Soon so the permission gate is visible.
 */
final class LegalMenuContributor implements MenuContributor
{
    public function groups(User $user, MenuMode $mode): array
    {
        if ($mode !== MenuMode::Admin) {
            return [];
        }

        return [
            new NavGroup('admin-apps', 'Apps and content', [
                NavItem::soon(
                    'legal-pages',
                    'Legal pages',
                    'scale',
                    AccessMatrix::LEGAL_PAGES_MANAGE,
                    description: 'Draft, preview and publish privacy, cookies, terms and related pages',
                ),
                NavItem::soon(
                    'legal-footer-links',
                    'Footer links',
                    'link',
                    AccessMatrix::LEGAL_PAGES_MANAGE,
                    description: 'Choose and order the links in the site footer',
                ),
                NavItem::soon(
                    'legal-acceptance',
                    'Acceptance',
                    'clipboard-check',
                    AccessMatrix::LEGAL_PAGES_MANAGE,
                    description: 'Who has accepted the current Terms of use',
                ),
                NavItem::soon(
                    'legal-audit',
                    'Legal audit log',
                    'scroll-text',
                    AccessMatrix::LEGAL_PAGES_MANAGE,
                    description: 'Draft, publish, restore, footer and export events',
                ),
            ], icon: 'app-window'),
        ];
    }
}
