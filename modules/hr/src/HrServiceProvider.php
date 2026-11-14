<?php

namespace Modules\Hr;

use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use Illuminate\Support\ServiceProvider;
use Modules\Hr\Console\RemindIdentityCheckExpiry;
use Modules\Hr\Navigation\HrMenuContributor;
use Modules\Hr\Services\LeaveRequestApprovedLeaveLookup;

class HrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hr.php', 'hr');
        $this->app->singleton(HrMenuContributor::class);
        $this->app->tag([HrMenuContributor::class], 'intranet.menu');
        $this->app->bind(ApprovedLeaveLookup::class, LeaveRequestApprovedLeaveLookup::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'hr');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([RemindIdentityCheckExpiry::class]);
    }
}
