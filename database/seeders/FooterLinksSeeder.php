<?php

namespace Database\Seeders;

use App\Core\Legal\FooterLinkGroup;
use App\Core\Legal\FooterLinkTargetType;
use App\Core\Legal\LegalPageVisibility;
use App\Models\FooterLink;
use Illuminate\Database\Seeder;

class FooterLinksSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            [
                'group' => FooterLinkGroup::Legal,
                'label' => 'Privacy notice',
                'icon' => null,
                'target_type' => FooterLinkTargetType::LegalPage,
                'target_value' => 'privacy',
                'visibility' => LegalPageVisibility::Everyone,
                'sort_order' => 10,
                'locked' => true,
                'module_slug' => null,
            ],
            [
                'group' => FooterLinkGroup::Legal,
                'label' => 'Cookies',
                'icon' => null,
                'target_type' => FooterLinkTargetType::LegalPage,
                'target_value' => 'cookies',
                'visibility' => LegalPageVisibility::Everyone,
                'sort_order' => 20,
                'locked' => true,
                'module_slug' => null,
            ],
            [
                'group' => FooterLinkGroup::Legal,
                'label' => 'Terms of use',
                'icon' => null,
                'target_type' => FooterLinkTargetType::LegalPage,
                'target_value' => 'terms',
                'visibility' => LegalPageVisibility::Everyone,
                'sort_order' => 30,
                'locked' => false,
                'module_slug' => null,
            ],
            [
                'group' => FooterLinkGroup::Legal,
                'label' => 'Accessibility statement',
                'icon' => null,
                'target_type' => FooterLinkTargetType::LegalPage,
                'target_value' => 'accessibility',
                'visibility' => LegalPageVisibility::Everyone,
                'sort_order' => 40,
                'locked' => false,
                'module_slug' => null,
            ],
            [
                'group' => FooterLinkGroup::TrustAndHelp,
                'label' => 'Hosting and security',
                'icon' => null,
                'target_type' => FooterLinkTargetType::LegalPage,
                'target_value' => 'hosting-and-security',
                'visibility' => LegalPageVisibility::SignedIn,
                'sort_order' => 10,
                'locked' => false,
                'module_slug' => null,
            ],
        ];

        foreach ($links as $definition) {
            FooterLink::query()->updateOrCreate(
                [
                    'target_type' => $definition['target_type'],
                    'target_value' => $definition['target_value'],
                ],
                $definition,
            );
        }
    }
}
