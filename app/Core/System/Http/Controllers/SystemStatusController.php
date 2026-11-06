<?php

namespace App\Core\System\Http\Controllers;

use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Core\System\SystemStatus;
use Illuminate\View\View;

class SystemStatusController
{
    public function __invoke(SystemStatus $status, AuditLogger $audit): View
    {
        $status->run();
        $audit->record('admin.system.viewed');

        $directorySync = AuditLog::query()
            ->where('event', 'people.directory_synced')
            ->orderByDesc('occurred_at')
            ->first();

        return view('core.admin.system.index', [
            'addons' => $status->summary(),
            'directorySync' => $directorySync,
        ]);
    }
}
