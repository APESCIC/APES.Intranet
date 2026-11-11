<?php

namespace Modules\Helpdesk\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Helpdesk\HelpdeskPermissions;
use Modules\Helpdesk\Models\Ticket;
use Modules\Helpdesk\Services\Reports;
use Modules\Helpdesk\Support\Csv;

class ReportController
{
    public function index(Request $request, Reports $reports): View
    {
        [$from, $to] = $this->period($request);
        $queues = HelpdeskPermissions::queuesFor($request->user());

        return view('helpdesk::reports.index', [
            'from' => $from,
            'to' => $to,
            'rows' => $reports->summary($queues, $from, $to),
        ]);
    }

    public function csv(Request $request, Reports $reports): Response
    {
        [$from, $to] = $this->period($request);
        $rows = $reports->summary(HelpdeskPermissions::queuesFor($request->user()), $from, $to);

        $csv = Csv::build(
            ['Queue', 'From', 'To', 'Raised', 'Still open', 'Resolved or closed', 'Missed an SLA target', 'Average first reply (hours)'],
            array_map(fn (string $queue, array $row) => [
                Ticket::QUEUES[$queue], $from->toDateString(), $to->toDateString(),
                $row['raised'], $row['open'], $row['closed'], $row['missed'], $row['average_first_reply_hours'],
            ], array_keys($rows), $rows),
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="help-desk-'.$from->toDateString().'-to-'.$to->toDateString().'.csv"',
        ]);
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function period(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('from') ? 'after_or_equal:from' : null]),
        ]);
        $to = isset($data['to']) ? CarbonImmutable::parse($data['to']) : CarbonImmutable::today();
        $from = isset($data['from']) ? CarbonImmutable::parse($data['from']) : $to->subDays(29);

        return [$from, $to];
    }
}
