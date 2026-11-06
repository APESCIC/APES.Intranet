<x-layouts.app title="Organisation chart" subtitle="Built from manager links.">
    <div class="mb-4 flex gap-3" role="tablist" aria-label="Organisation chart views">
        <a href="#chart-tree" class="sg-btn sg-btn-secondary sg-btn-sm">Tree</a>
        <a href="#chart-list" class="sg-btn sg-btn-ghost sg-btn-sm">Accessible list</a>
    </div>

    <section id="chart-tree" class="sg-card mb-6 overflow-x-auto p-4" aria-label="Organisation tree">
        @include('people::org-chart.partials.tree', ['nodes' => $tree])
    </section>

    <section id="chart-list" class="sg-card p-4" aria-label="Organisation list">
        <ul class="space-y-2">
            @foreach ($list as $person)
                <li>
                    <a class="underline" href="{{ route('people.directory.show', $person) }}">{{ $person->name }}</a>
                    @if ($person->manager)
                        <span class="text-sg-muted">reports to {{ $person->manager->name }}</span>
                    @else
                        <span class="text-sg-muted">top level</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.app>
