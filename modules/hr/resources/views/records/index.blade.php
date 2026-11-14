<x-layouts.app title="HR records" subtitle="HR and management only. Every open is audited.">
    <form method="get" action="{{ route('hr.records.index') }}" class="mb-6 flex flex-wrap gap-2" role="search">
        <label class="sr-only" for="hr-q">Search people</label>
        <input id="hr-q" class="sg-input max-w-md" type="search" name="q" value="{{ $q }}" placeholder="Name, email or job title">
        <button class="sg-btn sg-btn-secondary sg-btn-sm" type="submit">Search</button>
    </form>

    <section class="sg-card overflow-x-auto p-0" aria-labelledby="hr-people">
        <h2 id="hr-people" class="sr-only">People</h2>
        <table class="w-full text-left text-sm">
            <thead class="border-b border-sg-border bg-sg-mist/40 text-sg-muted">
                <tr>
                    <th class="px-4 py-3 font-medium" scope="col">Name</th>
                    <th class="px-4 py-3 font-medium" scope="col">Email</th>
                    <th class="px-4 py-3 font-medium" scope="col">Status</th>
                    <th class="px-4 py-3 font-medium" scope="col"><span class="sr-only">Open</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($people as $person)
                    <tr class="border-b border-sg-border/70" data-person="{{ $person->id }}">
                        <td class="px-4 py-3 font-medium">{{ $person->name }}</td>
                        <td class="px-4 py-3">{{ $person->email }}</td>
                        <td class="px-4 py-3">{{ $person->hrRecord?->employment_status ?: '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('hr.records.show', $person) }}">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-sg-muted" colspan="4">No people matched.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <div class="mt-4">{{ $people->links() }}</div>
</x-layouts.app>
