<?php

namespace App\Core\AdminDashboard;

use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;

/**
 * Builds the Admin panel dashboard from tagged contributors, hiding cards for
 * switched-off modules and permissions the person does not have.
 */
final class AdminDashboard
{
    public function __construct(
        private readonly Application $app,
        private readonly ModuleRegistry $modules,
    ) {}

    /**
     * @return list<array{
     *     key: string,
     *     title: string,
     *     body: string,
     *     href: ?string,
     *     permission: string,
     *     module?: string|null,
     *     tone?: string,
     *     icon?: string,
     *     external?: bool
     * }>
     */
    public function cardsFor(User $user): array
    {
        $cards = [];

        foreach ($this->app->tagged('intranet.admin-dashboard') as $contributor) {
            if (! $contributor instanceof AdminDashboardCardContributor) {
                continue;
            }

            if ($contributor instanceof BelongsToModule && ! $this->modules->enabled($contributor->moduleSlug())) {
                continue;
            }

            foreach ($contributor->cards($user) as $card) {
                if (! $this->maySee($user, $card)) {
                    continue;
                }

                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * @param  array{permission: string, module?: string|null}  $card
     */
    private function maySee(User $user, array $card): bool
    {
        $module = $card['module'] ?? null;
        if (is_string($module) && $module !== '' && ! $this->modules->enabled($module)) {
            return false;
        }

        return $user->can($card['permission']);
    }
}
