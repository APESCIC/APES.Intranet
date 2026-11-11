@props(['menu'])
<nav class="sg-nav" aria-label="Main menu" data-workspace-nav>
    @foreach ($menu as $group)
        <div>
            <p id="nav-heading-{{ $group->key }}" class="sg-nav-heading">{{ $group->heading }}</p>
            <ul class="sg-nav-list" aria-labelledby="nav-heading-{{ $group->key }}">
                @foreach ($group->items as $item)
                    <li>
                        @if ($item->isSoon())
                            <span class="sg-nav-link sg-nav-link--soon" data-nav-key="{{ $item->key }}">
                                <i data-lucide="{{ $item->icon }}"></i>
                                <span>{{ $item->label }}</span>
                                <span class="sg-nav-soon">Soon<span class="sr-only">, not available yet</span></span>
                            </span>
                        @elseif ($item->isExternal())
                            <a href="{{ $item->url() }}" class="sg-nav-link" data-nav-key="{{ $item->key }}" target="_blank" rel="noopener">
                                <i data-lucide="{{ $item->icon }}"></i>
                                <span>{{ $item->label }}</span>
                                <i data-lucide="external-link" class="sg-nav-external"></i>
                                <span class="sr-only">(opens in a new tab)</span>
                            </a>
                        @else
                            <a
                                href="{{ $item->url() }}"
                                class="sg-nav-link"
                                data-nav-key="{{ $item->key }}"
                                @if ($item->isActive()) aria-current="page" @endif
                            >
                                <i data-lucide="{{ $item->icon }}"></i>
                                <span>{{ $item->label }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
            @if ($group->note)
                <p class="mx-3 mt-1 mb-2 text-xs leading-snug text-sg-muted" data-nav-note="{{ $group->key }}">
                    {{ $group->note }}
                    @foreach ($group->noteLinks as $label => $url)
                        <a href="{{ $url }}" class="font-semibold text-sg-teal-dark underline">{{ $label }}</a>@if (! $loop->last) · @endif
                    @endforeach
                </p>
            @endif
        </div>
    @endforeach
</nav>
