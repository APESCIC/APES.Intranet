<?php

namespace App\Core\Modules;

use App\Core\Audit\AuditLogger;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Shared checks for schedules, jobs, listeners, notifications and outbound
 * integration clients when a module is disabled.
 */
final class ModuleGuards
{
    public static function enabled(string $slug): bool
    {
        return app(ModuleRegistry::class)->enabled($slug);
    }

    /**
     * For Schedule::...->when(ModuleGuards::whenEnabled('people')).
     *
     * @return \Closure(): bool
     */
    public static function whenEnabled(string $slug): \Closure
    {
        return static fn (): bool => self::enabled($slug);
    }

    /**
     * Refuse a new job dispatch when the module is off.
     */
    public static function assertCanDispatch(string $slug): void
    {
        if (! self::enabled($slug)) {
            throw ModuleDisabledException::forSlug($slug, 'dispatch jobs');
        }
    }

    /**
     * Listeners return straight away when the module is off.
     */
    public static function listenerShouldRun(string $slug): bool
    {
        return self::enabled($slug);
    }

    /**
     * Notifications are not sent when the module is off.
     */
    public static function assertCanNotify(string $slug): void
    {
        if (! self::enabled($slug)) {
            throw ModuleDisabledException::forSlug($slug, 'send notifications');
        }
    }

    /**
     * Outbound integration clients refuse and log when the Integration is off.
     */
    public static function assertCanCallIntegration(string $slug): void
    {
        if (self::enabled($slug)) {
            return;
        }

        Log::warning('Outbound integration call refused because the module is disabled.', [
            'module' => $slug,
        ]);

        throw ModuleDisabledException::forSlug($slug, 'call the integration');
    }

    /**
     * Handle a queued job that is about to run while its module is disabled.
     * Returns true when the worker should skip/delete the job.
     */
    public static function handleDisabledQueuedJob(ModuleAware $job): string
    {
        $slug = $job->moduleSlug();

        if (self::enabled($slug)) {
            return 'run';
        }

        if ($job->isCancellableWhenModuleDisabled()) {
            try {
                app(AuditLogger::class)->record('admin.module.job_cancelled', null, [
                    'reason' => "Module [{$slug}] disabled",
                    'job' => $job::class,
                ]);
            } catch (Throwable) {
                // Audit may be unavailable in some queue contexts; still cancel.
            }

            return 'cancel';
        }

        return 'pause';
    }
}
