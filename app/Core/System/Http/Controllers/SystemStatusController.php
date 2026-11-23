<?php

namespace App\Core\System\Http\Controllers;

use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Core\Modules\ModuleRegistry;
use App\Core\System\SystemStatus;
use App\Core\Version\AppVersion;
use Illuminate\View\View;

class SystemStatusController
{
    public function __invoke(SystemStatus $status, AuditLogger $audit, AppVersion $appVersion, ModuleRegistry $modules): View
    {
        $status->run();
        $audit->record('admin.system.viewed');

        $directorySync = AuditLog::query()
            ->where('event', 'people.directory_synced')
            ->orderByDesc('occurred_at')
            ->first();

        $versions = collect([
            ['name' => 'Core', 'slug' => 'core', 'version' => $appVersion->current()],
        ])->concat(
            $modules->manifests()->map(fn ($manifest) => [
                'name' => $manifest->name,
                'slug' => $manifest->slug,
                'version' => $manifest->version,
            ]),
        );

        return view('core.admin.system.index', [
            'addons' => $status->summary(),
            'directorySync' => $directorySync,
            'versions' => $versions,
        ]);
    }
}
