@props(['menu'])
<nav class="sg-top-nav" aria-label="Main menu" data-workspace-top-nav>
    @foreach ($menu as $group)
        @php
            $panelId = 'top-nav-panel-'.$group->key;
            $groupActive = collect($group->items)->contains(fn ($item) => $item->isActive());
            $homeItem = $group->key === 'home' ? ($group->items[0] ?? null) : null;
        @endphp
        @if ($homeItem)
            <a
                href="{{ $homeItem->url() }}"
                class="sg-top-nav__link"
                data-nav-key="{{ $homeItem->key }}"
                data-workspace-top-nav-home
                @if ($homeItem->isActive()) aria-current="page" @endif
            >
                {{ $group->heading }}
            </a>
        @else
            <div
                class="sg-disclosure"
                data-workspace-disclosure
                data-workspace-top-nav-item
                data-top-nav-heading="{{ $group->heading }}"
                @if ($groupActive) data-active="true" @endif
            >
                <button
                    type="button"
                    class="sg-disclosure__trigger"
                    aria-expanded="false"
                    aria-controls="{{ $panelId }}"
                    data-workspace-disclosure-trigger
                >
                    <span>{{ $group->heading }}</span>
                    <i data-lucide="chevron-down" class="sg-disclosure__chevron" aria-hidden="true"></i>
                </button>
                <div
                    id="{{ $panelId }}"
                    class="sg-disclosure__panel"
                    hidden
                    data-workspace-disclosure-panel
                >
                    <p id="top-nav-heading-{{ $group->key }}" class="sr-only">{{ $group->heading }}</p>
                    <x-shell.nav-items :group="$group" id-prefix="top-nav" />
                </div>
            </div>
        @endif
    @endforeach

    <div class="sg-disclosure sg-top-nav__more" data-workspace-disclosure data-workspace-top-nav-more hidden>
        <button
            type="button"
            class="sg-disclosure__trigger"
            aria-expanded="false"
            aria-controls="top-nav-panel-more"
            data-workspace-disclosure-trigger
        >
            <span>More sections</span>
            <i data-lucide="chevron-down" class="sg-disclosure__chevron" aria-hidden="true"></i>
        </button>
        <div
            id="top-nav-panel-more"
            class="sg-disclosure__panel sg-top-nav__more-panel"
            hidden
            data-workspace-disclosure-panel
            data-workspace-top-nav-more-panel
        ></div>
    </div>
</nav>
