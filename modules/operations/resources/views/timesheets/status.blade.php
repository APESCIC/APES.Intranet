<span @class([
    'sg-badge',
    'sg-badge--ok' => $timesheet->status === 'approved',
    'sg-badge--warn' => $timesheet->status === 'returned',
])>{{ ucfirst($timesheet->status) }}{{ $timesheet->isLocked() ? ' · locked' : '' }}</span>
