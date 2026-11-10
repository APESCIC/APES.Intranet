<?php

namespace Modules\Operations;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Operations\Console\AlertMissedChecklists;
use Modules\Operations\Console\FlagMissedClockOuts;
use Modules\Operations\Dashboard\OperationsDashboardCards;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\Task;
use Modules\Operations\Navigation\OperationsMenuContributor;
use Modules\Operations\Policies\ChecklistRunPolicy;
use Modules\Operations\Policies\TaskPolicy;

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
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'operations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([FlagMissedClockOuts::class, AlertMissedChecklists::class]);

        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(ChecklistRun::class, ChecklistRunPolicy::class);
    }
}
