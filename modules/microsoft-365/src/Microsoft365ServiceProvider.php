<?php

namespace Modules\Microsoft365;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Microsoft365\Dashboard\Microsoft365DashboardCards;
use Modules\Microsoft365\Models\MicrosoftLink;
use Modules\Microsoft365\Navigation\Microsoft365MenuContributor;
use Modules\Microsoft365\Policies\MicrosoftLinkPolicy;

class Microsoft365ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/microsoft-365.php', 'microsoft-365');
        $this->app->singleton(Microsoft365MenuContributor::class);
        $this->app->tag([Microsoft365MenuContributor::class], 'intranet.menu');
        $this->app->singleton(Microsoft365DashboardCards::class);
        $this->app->tag([Microsoft365DashboardCards::class], 'intranet.dashboard');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'microsoft-365');
        Gate::policy(MicrosoftLink::class, MicrosoftLinkPolicy::class);
    }
}
