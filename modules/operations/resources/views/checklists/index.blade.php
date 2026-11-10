<x-layouts.app title="Checklists" subtitle="Routine checks for animal care and sites, attached to each shift.">
    @include('operations::partials.flash')

    @if ($canManage)
        <p class="mb-4"><a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('operations.checklists.templates.index') }}">Checklist templates</a></p>
    @endif

    <section class="sg-card mb-6 p-6" aria-labelledby="mine-heading">
        <h2 id="mine-heading" class="mb-3 font-serif text-xl font-semibold">On my shifts</h2>
        @include('operations::checklists.list', ['runs' => $mine, 'label' => 'Checklists on my shifts', 'showMember' => false, 'empty' => 'No checklists on your shifts right now.'])
    </section>

    @if ($team->isNotEmpty())
        <section class="sg-card p-6" aria-labelledby="team-heading">
            <h2 id="team-heading" class="mb-3 font-serif text-xl font-semibold">Teams you look after (last 7 days)</h2>
            @include('operations::checklists.list', ['runs' => $team, 'label' => 'Team checklists', 'showMember' => true, 'empty' => ''])
        </section>
    @endif
</x-layouts.app>
