<?php

namespace App\Core\Legal\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Legal\AcceptanceReport;
use App\Core\Legal\LegalAuditEvents;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AdminLegalAcceptanceController
{
    public function __construct(private readonly AcceptanceReport $report) {}

    public function index(Request $request): View
    {
        $filter = $this->normaliseFilter($request);
        $data = $this->report->build($filter);

        return view('core.legal.admin.acceptance.index', [
            ...$data,
            'filter' => $filter,
        ]);
    }

    public function export(Request $request, AuditLogger $audit): StreamedResponse
    {
        $filter = $this->normaliseFilter($request);
        $data = $this->report->build($filter);
        $version = $data['version'];
        $rows = $data['rows'];
        $versionNumber = $version !== null ? $version->number : 0;
        $filename = 'terms-acceptance-v'.$versionNumber.'.csv';

        $audit->record(LegalAuditEvents::ACCEPTANCE_EXPORTED, $version, [
            'version' => $versionNumber,
            'count' => count($rows),
            'filter' => $filter,
        ], $request->user());

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['name', 'email', 'status', 'accepted_at', 'version']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['user']->name,
                    $row['user']->email,
                    $row['status'],
                    $row['accepted_at']?->toIso8601String() ?? '',
                    $row['version_number'] ?? '',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function normaliseFilter(Request $request): string
    {
        $filter = (string) $request->query('status', 'all');

        return in_array($filter, ['accepted', 'pending', 'all'], true) ? $filter : 'all';
    }
}
