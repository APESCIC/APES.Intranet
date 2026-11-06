<x-layouts.app title="Dashboard" :subtitle="'Welcome, '.auth()->user()->name.'.'">
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($cards as $card)
            <section class="sg-card p-5" aria-labelledby="card-{{ $card['key'] }}">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="card-{{ $card['key'] }}" class="font-serif text-xl font-semibold">{{ $card['title'] }}</h2>
                    @if (! empty($card['dismissible']))
                        <form method="post" action="{{ route('dashboard.dismiss', $card['key']) }}">
                            @csrf
                            <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Dismiss</button>
                        </form>
                    @endif
                </div>
                <p class="mt-2 text-sg-muted">{{ $card['body'] }}</p>
                @if (! empty($card['apps']))
                    <ul class="mt-4 grid gap-2">
                        @foreach ($card['apps'] as $app)
                            <li>
                                <a href="{{ $app->url }}" class="sg-btn sg-btn-secondary sg-btn-sm" target="_blank" rel="noopener">
                                    <i data-lucide="{{ $app->icon }}"></i>
                                    {{ $app->name }}
                                    <i data-lucide="external-link" aria-hidden="true"></i>
                                    <span class="sr-only">(opens in a new tab)</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @elseif (! empty($card['href']))
                    <a
                        href="{{ $card['href'] }}"
                        class="sg-btn sg-btn-secondary sg-btn-sm mt-4"
                        @if (! empty($card['external'])) target="_blank" rel="noopener" @endif
                    >
                        Open
                        @if (! empty($card['external']))
                            <span class="sr-only"> (opens in a new tab)</span>
                        @endif
                    </a>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.app>
