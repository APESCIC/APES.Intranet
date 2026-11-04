<?php

namespace App\Core;

use App\Core\Access\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
    }
}
