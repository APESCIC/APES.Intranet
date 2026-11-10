@php
    $timesheetLinks = [['operations.timesheets.index', 'My timesheets']];
    if (auth()->user()->can(\App\Core\Access\AccessMatrix::TEAM_SUPERVISE)) {
        $timesheetLinks[] = ['operations.timesheets.review', 'To approve'];
    }
    if (auth()->user()->can(\Modules\Operations\OperationsPermissions::HOURS_EXPORT)) {
        $timesheetLinks[] = ['operations.timesheets.hours', 'Volunteer hours'];
    }
@endphp
@if (count($timesheetLinks) > 1)
    <nav class="mb-6 flex flex-wrap gap-2" aria-label="Timesheets">
        @foreach ($timesheetLinks as [$routeName, $label])
            <a href="{{ route($routeName) }}" @class(['sg-btn sg-btn-sm', 'sg-btn-primary' => request()->routeIs($routeName), 'sg-btn-secondary' => ! request()->routeIs($routeName)]) @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
@endif
