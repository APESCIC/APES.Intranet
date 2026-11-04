<?php

namespace App\Core;

use App\Core\Access\Policies\RolePolicy;
use App\Core\Navigation\Menu;
use App\Core\Qa\QaLogin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;
use Spatie\Permission\Models\Role;

class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);

        View::composer('components.layouts.app', function (ViewInstance $view) {
            $user = auth()->user();
            $user = $user instanceof User ? $user : null;

            $view->with([
                'menu' => Menu::for($user),
                'shellUser' => $user,
                'qaEnabled' => QaLogin::enabled(),
            ]);
        });
    }
}
