@php
    $tone = $card['tone'] ?? null;
@endphp
<article
    @class(['sg-card sg-dashboard-card p-5', 'sg-dashboard-card--warn' => $tone === 'warn'])
    aria-labelledby="card-{{ $card['key'] }}"
    data-card="{{ $card['key'] }}"
>
    <div class="sg-dashboard-card__header">
        <h3 id="card-{{ $card['key'] }}" class="sg-type-section">{{ $card['title'] }}</h3>
        @if (! empty($card['dismissible']))
            <form method="post" action="{{ route('dashboard.dismiss', $card['key']) }}">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Dismiss</button>
            </form>
        @endif
    </div>
    <p class="sg-dashboard-card__body sg-type-body text-sg-muted">{{ $card['body'] }}</p>
    @if (! empty($card['apps']))
        <ul class="sg-app-tiles" role="list">
            @foreach ($card['apps'] as $app)
                <li>
                    <a href="{{ $app->url }}" class="sg-app-tile" target="_blank" rel="noopener">
                        <i data-lucide="{{ $app->icon }}" aria-hidden="true"></i>
                        <span>{{ $app->name }}</span>
                        <span class="sr-only"> (opens in a new tab)</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if (! empty($card['note']))
            <p class="sg-dashboard-card__note sg-type-meta" data-card-note>
                {{ $card['note'] }}
                @foreach ($card['note_links'] ?? [] as $label => $url)
                    <a href="{{ $url }}" class="font-semibold text-sg-teal-dark underline">{{ $label }}</a>@if (! $loop->last) · @endif
                @endforeach
            </p>
        @endif
    @elseif (! empty($card['href']))
        <a
            href="{{ $card['href'] }}"
            class="sg-btn sg-btn-secondary sg-btn-sm sg-dashboard-card__action"
            @if (! empty($card['external'])) target="_blank" rel="noopener" @endif
        >
            Open
            @if (! empty($card['external']))
                <span class="sr-only"> (opens in a new tab)</span>
            @endif
        </a>
    @endif
</article>