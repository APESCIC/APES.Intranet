<?php

namespace Modules\Microsoft365\Dashboard;

use App\Core\Dashboard\DashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Models\User;
use Modules\Microsoft365\Services\MicrosoftLinkRegistry;
use Modules\Microsoft365\Support\MicrosoftHelp;

final class Microsoft365DashboardCards implements BelongsToModule, DashboardCardContributor
{
    public function __construct(private readonly MicrosoftLinkRegistry $links) {}

    public function moduleSlug(): string
    {
        return 'microsoft-365';
    }

    public function cards(User $user): array
    {
        $cards = [];
        $apps = $this->links->forUser($user);
        if ($apps->isNotEmpty()) {
            $cards[] = [
                'key' => 'm365-apps',
                'title' => 'Microsoft 365 apps',
                'body' => 'Open the apps you use at APES.',
                'href' => null,
                'apps' => $apps,
                'note' => MicrosoftHelp::note(),
                'note_links' => MicrosoftHelp::links(),
            ];
        }

        return $cards;
    }
}
