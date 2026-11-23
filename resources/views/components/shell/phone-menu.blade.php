@props(['menu'])
<div class="sg-phone-menu-dock" data-workspace-phone-menu hidden>
    <h2 id="phone-menu-title" class="sr-only">Main menu</h2>
    <div
        class="sg-overlay"
        data-workspace-phone-menu-overlay
        data-motion
        data-state="closed"
        hidden
    >
        <div
            id="phone-menu-panel"
            class="sg-phone-menu"
            role="dialog"
            aria-modal="true"
            aria-labelledby="phone-menu-title"
            data-workspace-phone-menu-panel
        >
            <header class="sg-phone-menu__header">
                <p class="sg-phone-menu__title" aria-hidden="true">Menu</p>
                <button
                    type="button"
                    class="sg-icon-btn"
                    data-workspace-phone-menu-close
                    aria-label="Close menu"
                >
                    <i data-lucide="x" aria-hidden="true"></i>
                </button>
            </header>
            <div class="sg-phone-menu__body">
                <nav class="sg-phone-menu__nav" aria-label="Main menu sections" data-workspace-phone-menu-nav>
                    @foreach ($menu as $group)
                        @php
                            $panelId = 'phone-menu-group-'.$group->key;
                            $groupActive = collect($group->items)->contains(fn ($item) => $item->isActive());
                            $homeItem = $group->key === 'home' ? ($group->items[0] ?? null) : null;
                        @endphp
                        @if ($homeItem)
                            <a
                                href="{{ $homeItem->url() }}"
                                class="sg-phone-menu__home"
                                data-nav-key="{{ $homeItem->key }}"
                                @if ($homeItem->isActive()) aria-current="page" @endif
                            >
                                <i data-lucide="{{ $group->sectionIcon() }}" class="sg-phone-menu__icon" aria-hidden="true"></i>
                                <span>{{ $group->heading }}</span>
                            </a>
                        @else
                            <div
                                class="sg-phone-menu__group"
                                data-workspace-phone-disclosure
                                @if ($groupActive) data-active="true" @endif
                            >
                                <button
                                    type="button"
                                    class="sg-phone-menu__group-trigger"
                                    aria-expanded="false"
                                    aria-controls="{{ $panelId }}"
                                    data-workspace-phone-disclosure-trigger
                                >
                                    <i data-lucide="{{ $group->sectionIcon() }}" class="sg-phone-menu__icon" aria-hidden="true"></i>
                                    <span>{{ $group->heading }}</span>
                                    <i data-lucide="chevron-down" class="sg-phone-menu__chevron" aria-hidden="true"></i>
                                </button>
                                <div
                                    id="{{ $panelId }}"
                                    class="sg-phone-menu__group-panel"
                                    role="region"
                                    aria-labelledby="phone-menu-heading-{{ $group->key }}"
                                    hidden
                                    data-workspace-phone-disclosure-panel
                                >
                                    <h3 id="phone-menu-heading-{{ $group->key }}" class="sg-phone-menu__group-heading sr-only">{{ $group->heading }}</h3>
                                    <x-shell.nav-items :group="$group" id-prefix="phone-menu" />
                                </div>
                            </div>
                        @endif
                    @endforeach
                </nav>
                <x-shell.appearance-controls name-suffix="phone" />
            </div>
        </div>
    </div>
</div>