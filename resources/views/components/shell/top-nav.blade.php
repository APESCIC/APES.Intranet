@props(['menu'])
<nav class="sg-top-nav" aria-label="Main menu" data-workspace-top-nav>
    @foreach ($menu as $group)
        @php
            $panelId = 'top-nav-panel-'.$group->key;
            $groupActive = collect($group->items)->contains(fn ($item) => $item->isActive());
            $itemCount = count($group->items);
        @endphp
        <div class="sg-disclosure" data-workspace-disclosure @if ($groupActive) data-active="true" @endif>
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
                class="sg-disclosure__panel sg-mega-panel"
                @if ($itemCount >= 4) data-columns="2" @endif
                hidden
                data-workspace-disclosure-panel
            >
                <p id="top-nav-heading-{{ $group->key }}" class="sg-mega-panel__heading">{{ $group->heading }}</p>
                <x-shell.nav-items :group="$group" id-prefix="top-nav" />
            </div>
        </div>
    @endforeach
</nav>
