<x-layouts.app title="Admin" subtitle="Run the intranet. You see only the sections your permissions allow.">
    @forelse ($groups as $group)
        <section class="mb-6" aria-labelledby="admin-overview-{{ $group->key }}">
            <h2 id="admin-overview-{{ $group->key }}" class="font-serif text-xl font-semibold">{{ $group->heading }}</h2>
            <ul class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($group->items as $item)
                    <li>
                        <a href="{{ $item->url() }}" class="sg-card flex min-h-11 items-center gap-3 p-5 font-semibold" data-admin-section="{{ $item->key }}">
                            <i data-lucide="{{ $item->icon }}" aria-hidden="true"></i>
                            {{ $item->label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        <p class="text-sg-muted">There are no admin sections you can open.</p>
    @endforelse
</x-layouts.app>
