<?php

namespace App\Core\Modules;

/**
 * Marks a menu, dashboard or search contributor as belonging to a module so
 * Core can skip it when that module is disabled.
 */
interface BelongsToModule
{
    public function moduleSlug(): string;
}
