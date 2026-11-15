<?php

namespace App\Core\Modules;

use RuntimeException;

final class ModuleDisabledException extends RuntimeException
{
    public static function forSlug(string $slug, string $action = 'run'): self
    {
        return new self("Module [{$slug}] is disabled; cannot {$action}.");
    }
}
