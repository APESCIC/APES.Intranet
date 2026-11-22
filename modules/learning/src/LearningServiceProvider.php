<?php

namespace Modules\Learning;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Learning\Console\RemindCertificateExpiry;
use Modules\Learning\Gradebook\GradebookCalculator;
use Modules\Learning\Models\Course;
use Modules\Learning\Navigation\LearningMenuContributor;
use Modules\Learning\Policies\CoursePolicy;
use Modules\Learning\Policies\LearningGradePolicy;
use Modules\Learning\Services\AssignmentSubmissionService;
use Modules\Learning\Services\CertificateService;
use Modules\Learning\Services\CourseAuthoringService;
use Modules\Learning\Services\EnrolmentService;
use Modules\Learning\Services\GradeAuditService;
use Modules\Learning\Services\GradeReleaseService;
use Modules\Learning\Services\GradeViewExportService;
use Modules\Learning\Services\GradingSchemeService;
use Modules\Learning\Services\LearningAssignmentService;
use Modules\Learning\Services\LearningReportService;
use Modules\Learning\Services\ProgressService;
use Modules\Learning\Services\QuestionBankService;
use Modules\Learning\Services\QuizAttemptService;

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
        $this->app->singleton(CertificateService::class);
        $this->app->singleton(ProgressService::class);
        $this->app->singleton(QuestionBankService::class);
        $this->app->singleton(QuizAttemptService::class);
        $this->app->singleton(AssignmentSubmissionService::class);
        $this->app->singleton(GradingSchemeService::class);
        $this->app->singleton(GradebookCalculator::class);
        $this->app->singleton(GradeReleaseService::class);
        $this->app->singleton(GradeAuditService::class);
        $this->app->singleton(GradeViewExportService::class);
        $this->app->singleton(LearningReportService::class);
        $this->app->singleton(LearningGradePolicy::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'learning');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->commands([RemindCertificateExpiry::class]);

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::define('learning.grade.view', [LearningGradePolicy::class, 'view']);
        Gate::define('learning.grade.edit', [LearningGradePolicy::class, 'edit']);
        Gate::define('learning.grade.release', [LearningGradePolicy::class, 'release']);
    }
}
