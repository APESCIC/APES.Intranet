<?php

namespace App\Core;

use App\Core\Access\Policies\MatrixPolicies;
use App\Core\Access\Policies\RolePolicy;
use App\Core\Audit\Console\PruneAuditLog;
use App\Core\Auth\Cloudron\CloudronIdentityProvider;
use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\IdentityProvider;
use App\Core\Navigation\Menu;
use App\Core\Qa\QaLogin;
use App\Core\System\Checks\LdapCheck;
use App\Core\System\Checks\MysqlCheck;
use App\Core\System\Checks\OidcCheck;
use App\Core\System\Checks\RedisCheck;
use App\Core\System\Checks\SmtpCheck;
use App\Core\System\Console\RunSystemChecks;
use App\Core\System\SystemStatus;
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
        $this->app->bind(SystemStatus::class, fn ($app) => new SystemStatus([
            $app->make(MysqlCheck::class),
            $app->make(RedisCheck::class),
            $app->make(SmtpCheck::class),
            $app->make(LdapCheck::class),
            $app->make(OidcCheck::class),
        ]));
    }

    public function boot(): void
    {
        $this->commands([PruneAuditLog::class, RunSystemChecks::class]);

        Gate::policy(Role::class, RolePolicy::class);
        MatrixPolicies::register();

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
