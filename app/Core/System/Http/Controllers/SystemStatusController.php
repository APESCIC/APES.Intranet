<?php

namespace App\Core\System\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\System\SystemStatus;
use Illuminate\View\View;

class SystemStatusController
{
    public function __invoke(SystemStatus $status, AuditLogger $audit): View
    {
        $status->run();
        $audit->record('admin.system.viewed');

        return view('core.admin.system.index', ['addons' => $status->summary()]);
    }
}
