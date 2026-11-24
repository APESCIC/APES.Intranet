<?php

namespace App\Core\Legal;

use App\Core\Modules\ModuleRegistry;
use App\Models\FooterLink;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

final class FooterLinks
{
    public function __construct(
        private readonly ModuleRegistry $modules,
    ) {}

    /**
     * @return Collection<int, FooterLink>
     */
    public function forVisitor(?User $user): Collection
    {
        $signedIn = $user !== null;

        try {
            $links = FooterLink::query()
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get();
        } catch (QueryException) {
            // Error pages must still render when the database is down.
            return collect();
        }

        return $links
            ->filter(function (FooterLink $link) use ($signedIn): bool {
                if ($link->visibility === LegalPageVisibility::SignedIn && ! $signedIn) {
                    return false;
                }

                if ($link->module_slug !== null && ! $this->modules->enabled($link->module_slug)) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function href(FooterLink $link): ?string
    {
        return match ($link->target_type) {
            FooterLinkTargetType::LegalPage => $this->legalPageHref($link->target_value),
            FooterLinkTargetType::Route => Route::has($link->target_value)
                ? route($link->target_value)
                : null,
            FooterLinkTargetType::Url => $link->target_value,
            default => null,
        };
    }

    public function isExternal(FooterLink $link): bool
    {
        return $link->target_type === FooterLinkTargetType::Url;
    }

    private function legalPageHref(string $slug): ?string
    {
        $route = $slug === 'privacy' ? 'privacy' : 'legal.'.$slug;

        return Route::has($route) ? route($route) : null;
    }
}
