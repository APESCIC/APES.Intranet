<x-layouts.app title="Admin" subtitle="Run the intranet. You see only the cards your permissions allow.">
    @if (session('status'))
        <p class="sg-card mb-4 p-4" role="status">{{ session('status') }}</p>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        @if ($editing)
            <a href="{{ route('admin.overview') }}" class="sg-btn sg-btn-secondary sg-btn-sm">Cancel</a>
            <form method="post" action="{{ route('admin.overview.layout.reset') }}" class="inline">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Reset to default</button>
            </form>
        @else
            <a href="{{ route('admin.overview', ['edit' => 1]) }}" class="sg-btn sg-btn-secondary sg-btn-sm" data-admin-edit-layout>Edit layout</a>
        @endif
    </div>

    @if ($editing)
        <p class="mb-4 text-sg-muted" role="status">Edit layout: use Move up or Move down (no dragging required), hide cards, or add them back from the picker, then Save.</p>

        <ol class="mb-4 flex list-none flex-col gap-3 p-0" data-admin-layout-editor>
            @foreach ($flatCards as $index => $card)
                <li class="sg-card p-4" data-admin-card="{{ $card['key'] }}">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-semibold">{{ $card['title'] }}</span>
                        <div class="flex flex-wrap gap-2">
                            <form method="post" action="{{ route('admin.overview.layout.move') }}">
                                @csrf
                                <input type="hidden" name="key" value="{{ $card['key'] }}">
                                <input type="hidden" name="direction" value="up">
                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm" @disabled($index === 0) data-move-up="{{ $card['key'] }}">Move up</button>
                            </form>
                            <form method="post" action="{{ route('admin.overview.layout.move') }}">
                                @csrf
                                <input type="hidden" name="key" value="{{ $card['key'] }}">
                                <input type="hidden" name="direction" value="down">
                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm" @disabled($index === count($flatCards) - 1) data-move-down="{{ $card['key'] }}">Move down</button>
                            </form>
                            <form method="post" action="{{ route('admin.overview.layout.hide') }}">
                                @csrf
                                <input type="hidden" name="key" value="{{ $card['key'] }}">
                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm" data-hide-card="{{ $card['key'] }}">Hide</button>
                            </form>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>

        <form method="post" action="{{ route('admin.overview.layout.save') }}">
            @csrf
            @foreach ($flatCards as $card)
                <input type="hidden" name="ordered_keys[]" value="{{ $card['key'] }}">
            @endforeach
            @foreach ($hiddenCards as $card)
                <input type="hidden" name="hidden_keys[]" value="{{ $card['key'] }}">
            @endforeach
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save layout</button>
        </form>

        @if ($hiddenCards !== [])
            <section class="sg-card mt-6 p-5" aria-labelledby="admin-card-picker" data-admin-card-picker>
                <h2 id="admin-card-picker" class="font-serif text-lg font-semibold">Add a card</h2>
                <p class="mt-1 text-sg-muted">Only cards you may see are listed.</p>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach ($hiddenCards as $card)
                        <li>
                            <form method="post" action="{{ route('admin.overview.layout.show') }}">
                                @csrf
                                <input type="hidden" name="key" value="{{ $card['key'] }}">
                                <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm w-full justify-start" data-add-card="{{ $card['key'] }}">
                                    Add {{ $card['title'] }}
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @elseif ($groups === [])
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
