@props([
    'menu',
    'user',
    'qaEnabled' => false,
    'showMenuModeSwitcher' => false,
    'menuMode' => null,
])
<div class="sg-rail" data-workspace-rail>
    <div class="sg-rail__top">
        <x-shell.brand />
        <button type="button" class="sg-icon-btn" aria-label="Close menu" data-workspace-close hidden>
            <i data-lucide="x"></i>
        </button>
    </div>
    @if ($showMenuModeSwitcher && $menuMode)
        <form method="post" action="{{ route('communications.menu-mode') }}" class="sg-menu-mode" aria-label="Menu mode" data-panel-switch>
            @csrf
            <p class="sg-nav-heading" id="menu-mode-label">Menu mode</p>
            <div class="flex gap-2 px-3 pb-3" role="group" aria-labelledby="menu-mode-label">
                <button
                    type="submit"
                    name="mode"
                    value="staff"
                    class="sg-btn sg-btn-sm {{ $menuMode->value === 'staff' ? 'sg-btn-primary' : 'sg-btn-secondary' }}"
                    aria-pressed="{{ $menuMode->value === 'staff' ? 'true' : 'false' }}"
                    aria-label="Staff menu mode, currently {{ $menuMode->label() }}"
                >Staff</button>
                <button
                    type="submit"
                    name="mode"
                    value="admin"
                    class="sg-btn sg-btn-sm {{ $menuMode->value === 'admin' ? 'sg-btn-primary' : 'sg-btn-secondary' }}"
                    aria-pressed="{{ $menuMode->value === 'admin' ? 'true' : 'false' }}"
                    aria-label="Admin menu mode, currently {{ $menuMode->label() }}"
                >Admin</button>
            </div>
            <p class="px-3 pb-3 text-xs text-sg-muted">Navigation preference only. This is not the local QA role switcher.</p>
        </form>
    @endif
    <x-shell.nav :menu="$menu" />
    <x-shell.user-dock :user="$user" :qa-enabled="$qaEnabled" />
</div>
