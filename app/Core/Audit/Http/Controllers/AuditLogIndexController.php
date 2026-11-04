<?php

namespace App\Core\Audit\Http\Controllers;

use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use Illuminate\View\View;

class AuditLogIndexController
{
    public function __invoke(AuditLogger $audit): View
    {
        $audit->record('admin.audit.viewed');

        return view('core.admin.audit.index', [
            'entries' => AuditLog::query()->with('actor')->latest('occurred_at')->latest('id')->paginate(50),
            'retentionDays' => config('intranet.retention.audit_log_days'),
        ]);
    }
}
