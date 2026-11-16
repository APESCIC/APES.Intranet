<?php

namespace Modules\Learning;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Learning\Models\Course;
use Modules\Learning\Navigation\LearningMenuContributor;
use Modules\Learning\Policies\CoursePolicy;
use Modules\Learning\Policies\LearningGradePolicy;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\LearningAssignmentService;
use Modules\Learning\Services\ProgressService;

class LearningServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/learning.php', 'learning');
        $this->app->singleton(LearningMenuContributor::class);
        $this->app->tag([LearningMenuContributor::class], 'intranet.menu');
        $this->app->singleton(LearningAssignmentService::class);
        $this->app->singleton(CourseAuthoringService::class);
        $this->app->singleton(EnrolmentService::class);
        $this->app->singleton(ProgressService::class);
        $this->app->singleton(LearningGradePolicy::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'learning');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::define('learning.grade.view', [LearningGradePolicy::class, 'view']);
        Gate::define('learning.grade.edit', [LearningGradePolicy::class, 'edit']);
        Gate::define('learning.grade.release', [LearningGradePolicy::class, 'release']);
    }
}
