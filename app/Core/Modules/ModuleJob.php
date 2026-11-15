<?php

namespace App\Core\Modules;

use App\Core\Modules\Concerns\BelongsToModuleSlug;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Base for queued jobs that belong to a module. Dispatch only after
 * ModuleGuards::assertCanDispatch($this->moduleSlug()).
 */
abstract class ModuleJob implements ModuleAware, ShouldQueue
{
    use BelongsToModuleSlug;
}
