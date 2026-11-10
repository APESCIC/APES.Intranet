<x-layouts.app title="Tasks" subtitle="Operational work for people, teams and shifts. Team projects stay in Microsoft Planner.">
    @include('operations::partials.flash')

    <div class="mb-6 flex flex-wrap gap-2">
        @if ($canCreate)
            <a class="sg-btn sg-btn-primary sg-btn-sm" href="{{ route('operations.tasks.create') }}">Add a task</a>
        @endif
        <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('operations.tasks.index', $showDone ? [] : ['done' => 1]) }}">{{ $showDone ? 'Hide done tasks' : 'Show done tasks' }}</a>
    </div>

    <section class="sg-card mb-6 p-6" aria-labelledby="my-tasks">
        <h2 id="my-tasks" class="mb-3 font-serif text-xl font-semibold">My tasks</h2>
        @include('operations::tasks.list', ['tasks' => $mine, 'label' => 'My tasks', 'empty' => 'Nothing for you right now.'])
    </section>

    @if ($managed->isNotEmpty())
        <section class="sg-card p-6" aria-labelledby="team-tasks">
            <h2 id="team-tasks" class="mb-3 font-serif text-xl font-semibold">Tasks you look after</h2>
            @include('operations::tasks.list', ['tasks' => $managed, 'label' => 'Tasks you look after', 'empty' => ''])
        </section>
    @endif
</x-layouts.app>
