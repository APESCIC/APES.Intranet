<?php

namespace App\Core\Modules;

/**
 * Implemented by jobs, listeners and notifications that belong to a module.
 */
interface ModuleAware
{
    public function moduleSlug(): string;

    /**
     * When true, a queued job that has not started is deleted and audited
     * instead of being released back to the queue (paused).
     */
    public function isCancellableWhenModuleDisabled(): bool;
}
