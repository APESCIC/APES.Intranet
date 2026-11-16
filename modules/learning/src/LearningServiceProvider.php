<?php

namespace Modules\Learning;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Learning\Navigation\LearningMenuContributor;
use Modules\Learning\Policies\LearningGradePolicy;
use Modules\Learning\Services\LearningAssignmentService;

class LearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/learning.php', 'learning');
        $this->app->singleton(LearningMenuContributor::class);
        $this->app->tag([LearningMenuContributor::class], 'intranet.menu');
        $this->app->singleton(LearningAssignmentService::class);
        $this->app->singleton(LearningGradePolicy::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'learning');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::define('learning.grade.view', [LearningGradePolicy::class, 'view']);
        Gate::define('learning.grade.edit', [LearningGradePolicy::class, 'edit']);
        Gate::define('learning.grade.release', [LearningGradePolicy::class, 'release']);
    }
}
