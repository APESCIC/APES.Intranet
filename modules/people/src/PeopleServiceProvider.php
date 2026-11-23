<?php

namespace Modules\People;

use App\Core\Auth\Cloudron\LdapDirectoryGroups;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Modules\ModuleGuards;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\People\Console\RefreshTotpStatus;
use Modules\People\Console\SyncDirectory;
use Modules\People\Models\Placement;
use Modules\People\Models\PlacementHour;
use Modules\People\Navigation\PeopleMenuContributor;
use Modules\People\Policies\PlacementHourPolicy;
use Modules\People\Policies\PlacementPolicy;
use Modules\People\Policies\ProfilePolicy;

class PeopleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DirectoryRoster::class, LdapDirectoryGroups::class);

        $this->mergeConfigFrom(__DIR__.'/../config/people.php', 'people');

        $this->app->singleton(PeopleMenuContributor::class);
        $this->app->tag([PeopleMenuContributor::class], 'intranet.menu');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'people');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([SyncDirectory::class, RefreshTotpStatus::class]);

        Gate::policy(User::class, ProfilePolicy::class);
        Gate::policy(Placement::class, PlacementPolicy::class);
        Gate::policy(PlacementHour::class, PlacementHourPolicy::class);

        Schedule::command('people:sync-directory')
            ->dailyAt('02:15')
            ->withoutOverlapping()
            ->when(ModuleGuards::whenEnabled('people'));
        Schedule::command('people:refresh-totp-status')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->when(ModuleGuards::whenEnabled('people'));
    }
}
