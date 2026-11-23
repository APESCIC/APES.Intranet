<x-layouts.app title="Dashboard" :subtitle="'Welcome, '.auth()->user()->name.'.'">
    @if ($sections === [])
        <p class="sg-empty-state sg-card" role="status">Nothing on your dashboard right now. Check back after updates or events are published.</p>
    @else
        <div class="sg-dashboard">
            @foreach ($sections as $section)
                <section class="sg-dashboard__section" aria-labelledby="dashboard-section-{{ $section['key'] }}">
                    <h2 id="dashboard-section-{{ $section['key'] }}" class="sg-dashboard__heading sg-type-section font-semibold">{{ $section['heading'] }}</h2>
                    <div class="sg-dashboard__cards">
                        @foreach ($section['cards'] as $card)
                            @include('communications::dashboard.card', ['card' => $card])
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.app>