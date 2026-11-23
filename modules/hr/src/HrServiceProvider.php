<?php

namespace Modules\Hr;

use App\Core\Hr\Contracts\ApprovedLeaveLookup;
use App\Core\Modules\ModuleGuards;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Hr\Console\RemindIdentityCheckExpiry;
use Modules\Hr\Models\Recognition;
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

        // Keep People free of HR imports: inject recognitions onto the profile view.
        View::composer('people::profile.show', function ($view): void {
            $person = $view->getData()['person'] ?? null;
            if ($person === null) {
                $view->with('recognitions', collect());

                return;
            }

            $view->with('recognitions', Recognition::query()
                ->with('giver')
                ->where('recipient_id', $person->id)
                ->latest()
                ->limit(20)
                ->get());
        });

        Schedule::command('hr:remind-identity-check-expiry')
            ->dailyAt('09:15')
            ->withoutOverlapping()
            ->when(ModuleGuards::whenEnabled('hr'));
    }
}
