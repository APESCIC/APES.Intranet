<?php

namespace Modules\Helpdesk;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Helpdesk\Dashboard\HelpdeskDashboardCards;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Navigation\HelpdeskMenuContributor;
use Modules\Helpdesk\Policies\TicketPolicy;

class HelpdeskServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/helpdesk.php', 'helpdesk');
        $this->app->singleton(HelpdeskMenuContributor::class);
        $this->app->tag([HelpdeskMenuContributor::class], 'intranet.menu');
        $this->app->singleton(HelpdeskDashboardCards::class);
        $this->app->tag([HelpdeskDashboardCards::class], 'intranet.dashboard');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'helpdesk');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'helpdesk');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Ticket::class, TicketPolicy::class);
    }
}
