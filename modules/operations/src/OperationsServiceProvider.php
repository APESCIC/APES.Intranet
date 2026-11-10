<?php

namespace Modules\Operations;

use Illuminate\Support\ServiceProvider;
use Modules\Operations\Dashboard\OperationsDashboardCards;
use Modules\Operations\Navigation\OperationsMenuContributor;

class OperationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/operations.php', 'operations');
        $this->app->singleton(OperationsMenuContributor::class);
        $this->app->tag([OperationsMenuContributor::class], 'intranet.menu');
        $this->app->singleton(OperationsDashboardCards::class);
        $this->app->tag([OperationsDashboardCards::class], 'intranet.dashboard');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'operations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
