@php
    $rotaLinks = [
        ['operations.rota.index', 'My rota'],
        ['operations.rota.availability.index', 'My unavailability'],
    ];
    if (Route::has('operations.rota.open')) {
        $rotaLinks[] = ['operations.rota.open', 'Open shifts'];
        $rotaLinks[] = ['operations.rota.requests.index', 'Claims and swaps'];
    }
    if (auth()->user()->can(\Modules\Operations\OperationsPermissions::ROTA_MANAGE)) {
        $rotaLinks[] = ['operations.rota.templates.index', 'Shift templates'];
    }
@endphp
<nav class="mb-6 flex flex-wrap gap-2" aria-label="Rota">
    @foreach ($rotaLinks as [$routeName, $label])
        <a href="{{ route($routeName) }}" @class(['sg-btn sg-btn-sm', 'sg-btn-primary' => request()->routeIs($routeName), 'sg-btn-secondary' => ! request()->routeIs($routeName)]) @if (request()->routeIs($routeName)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
