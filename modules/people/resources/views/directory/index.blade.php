<x-layouts.app title="Directory" subtitle="Find staff, volunteers and students.">
    <form method="get" class="sg-card mb-6 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4" role="search">
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-semibold" for="q">Search</label>
            <input id="q" name="q" value="{{ $q }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2" placeholder="Name, email, team or title">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="type">Member type</label>
            <select id="type" name="type" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
                <option value="">Any</option>
                @foreach ($accountTypes as $accountType)
                    <option value="{{ $accountType->value }}" @selected($type === $accountType->value)>{{ $accountType->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="team">Team</label>
            <select id="team" name="team" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
                <option value="">Any</option>
                @foreach ($teams as $teamName)
                    <option value="{{ $teamName }}" @selected($team === $teamName)>{{ $teamName }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="skill">Skill</label>
            <input id="skill" name="skill" value="{{ $skill }}" class="w-full rounded-sg-field border border-sg-info-mist bg-white px-3 py-2">
        </div>
        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Search</button>
            <a href="{{ route('people.directory.index') }}" class="sg-btn sg-btn-ghost sg-btn-sm">Clear</a>
        </div>
    </form>

    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($people as $person)
            <li>
                <a href="{{ route('people.directory.show', $person) }}" class="sg-card block p-4 hover:bg-white/70">
                    <div class="flex items-center gap-3">
                        <span class="sg-avatar" aria-hidden="true">{{ $person->initials() }}</span>
                        <div>
                            <p class="font-semibold">{{ $person->name }}</p>
                            <p class="text-sm text-sg-muted">{{ $person->job_title ?: $person->accountTypeLabel() }}</p>
                            @if ($person->team)
                                <p class="text-sm text-sg-muted">{{ $person->team }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            </li>
        @empty
            <li class="sg-card p-6 text-sg-muted sm:col-span-2 lg:col-span-3">No people matched those filters.</li>
        @endforelse
    </ul>

    <div class="mt-6">{{ $people->links() }}</div>
</x-layouts.app>
