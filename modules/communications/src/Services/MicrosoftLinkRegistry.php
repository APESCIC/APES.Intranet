<?php

namespace Modules\Communications\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Communications\Models\MicrosoftLink;

class MicrosoftLinkRegistry
{
    /**
     * @return Collection<int, MicrosoftLink>
     */
    public function forUser(User $user): Collection
    {
        return MicrosoftLink::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (MicrosoftLink $link) => $link->isVisibleTo($user))
            ->values();
    }
}
