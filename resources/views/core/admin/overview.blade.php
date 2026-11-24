<x-layouts.app title="Admin" subtitle="Run the intranet. You see only the cards your permissions allow.">
    @if ($groups === [])
        <p class="sg-empty-state sg-card" role="status">There are no admin cards you can open.</p>
    @else
        <div class="sg-dashboard sg-admin-dashboard" data-admin-dashboard>
            @foreach ($groups as $group)
                <section class="sg-dashboard__section" aria-labelledby="admin-dashboard-{{ $group['key'] }}">
                    <h2 id="admin-dashboard-{{ $group['key'] }}" class="sg-dashboard__heading sg-type-section font-semibold">{{ $group['heading'] }}</h2>
                    <div class="sg-dashboard__cards">
                        @foreach ($group['cards'] as $card)
                            @php
                                $tone = $card['tone'] ?? null;
                                $sectionKey = $card['section'] ?? $card['key'];
                            @endphp
                            <article
                                @class(['sg-card sg-dashboard-card p-5', 'sg-dashboard-card--warn' => $tone === 'warn'])
                                aria-labelledby="admin-card-{{ $card['key'] }}"
                                data-admin-card="{{ $card['key'] }}"
                                data-admin-section="{{ $sectionKey }}"
                            >
                                <div class="sg-dashboard-card__header">
                                    <h3 id="admin-card-{{ $card['key'] }}" class="sg-type-section flex items-center gap-2">
                                        @if (! empty($card['icon']))
                                            <i data-lucide="{{ $card['icon'] }}" aria-hidden="true"></i>
                                        @endif
                                        {{ $card['title'] }}
                                    </h3>
                                </div>
                                <p @class(['sg-dashboard-card__body sg-type-body text-sg-muted', 'whitespace-pre-line' => ! empty($card['preserve_newlines'])])>{{ $card['body'] }}</p>
                                @if (! empty($card['chart']))
                                    <div class="mt-3 text-sg-teal-dark dark:text-sg-teal">{!! $card['chart'] !!}</div>
                                @endif
                                @if (! empty($card['href']))
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
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.app>
