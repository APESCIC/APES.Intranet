<?php

namespace App\Core;

use App\Core\Access\Policies\RolePolicy;
use App\Core\Auth\Cloudron\CloudronIdentityProvider;
use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
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
    public function register(): void
    {
        $this->app->bind(IdentityProvider::class, CloudronIdentityProvider::class);
        $this->app->bind(DirectoryGroups::class, LdapDirectoryGroups::class);
    }

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
