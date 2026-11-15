<?php

namespace App\Core\Modules\Concerns;

trait BelongsToModuleSlug
{
    abstract public function moduleSlug(): string;

    public function isCancellableWhenModuleDisabled(): bool
    {
        return false;
    }
}
