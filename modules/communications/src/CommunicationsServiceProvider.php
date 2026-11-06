<?php

namespace Modules\Communications;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Communications\Console\SendEventReminders;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\MicrosoftLink;
use Modules\Communications\Models\PolicyDocument;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Navigation\CommunicationsMenuContributor;
use Modules\Communications\Policies\EventPolicy;
use Modules\Communications\Policies\HandbookChapterPolicy;
use Modules\Communications\Policies\MicrosoftLinkPolicy;
use Modules\Communications\Policies\PolicyDocumentPolicy;
use Modules\Communications\Policies\UpdatePostPolicy;

class CommunicationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/communications.php', 'communications');
        $this->app->singleton(CommunicationsMenuContributor::class);
        $this->app->tag([CommunicationsMenuContributor::class], 'intranet.menu');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'communications');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([SendEventReminders::class]);

        Gate::policy(UpdatePost::class, UpdatePostPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(HandbookChapter::class, HandbookChapterPolicy::class);
        Gate::policy(PolicyDocument::class, PolicyDocumentPolicy::class);
        Gate::policy(MicrosoftLink::class, MicrosoftLinkPolicy::class);
    }
}
