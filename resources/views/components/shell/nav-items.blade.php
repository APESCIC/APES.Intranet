@props(['group', 'idPrefix' => 'nav'])
<ul class="sg-nav-list" aria-labelledby="{{ $idPrefix }}-heading-{{ $group->key }}">
    @foreach ($group->items as $item)
        <li>
            @if ($item->isSoon())
                <span class="sg-nav-link sg-nav-link--soon" data-nav-key="{{ $item->key }}">
                    <i data-lucide="{{ $item->icon }}"></i>
                    <span class="sg-nav-link__text">
                        <span class="sg-nav-link__label">{{ $item->label }}</span>
                        @if ($item->description)
                            <span class="sg-nav-link__desc">{{ $item->description }}</span>
                        @endif
                    </span>
                    <span class="sg-nav-soon">Soon<span class="sr-only">, not available yet</span></span>
                </span>
            @elseif ($item->isExternal())
                <a href="{{ $item->url() }}" class="sg-nav-link @if ($item->description) sg-nav-link--described @endif" data-nav-key="{{ $item->key }}" target="_blank" rel="noopener">
                    <i data-lucide="{{ $item->icon }}"></i>
                    <span class="sg-nav-link__text">
                        <span class="sg-nav-link__label">{{ $item->label }}</span>
                        @if ($item->description)
                            <span class="sg-nav-link__desc">{{ $item->description }}</span>
                        @endif
                    </span>
                    <i data-lucide="external-link" class="sg-nav-external"></i>
                    <span class="sr-only">(opens in a new tab)</span>
                </a>
            @else
                <a
                    href="{{ $item->url() }}"
                    class="sg-nav-link @if ($item->description) sg-nav-link--described @endif"
                    data-nav-key="{{ $item->key }}"
                    @if ($item->isActive()) aria-current="page" @endif
                >
                    <i data-lucide="{{ $item->icon }}"></i>
                    <span class="sg-nav-link__text">
                        <span class="sg-nav-link__label">{{ $item->label }}</span>
                        @if ($item->description)
                            <span class="sg-nav-link__desc">{{ $item->description }}</span>
                        @endif
                    </span>
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
