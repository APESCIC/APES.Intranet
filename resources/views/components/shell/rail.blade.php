@props([
    'menu',
    'user',
    'qaEnabled' => false,
    'panel' => null,
])
<div class="sg-rail" data-workspace-rail>
    <div class="sg-rail__top">
        <x-shell.brand :panel="$panel" />
        <button type="button" class="sg-icon-btn" aria-label="Close menu" data-workspace-close hidden>
            <i data-lucide="x"></i>
        </button>
    </div>
    @if ($panel)
        <nav class="sg-switch" aria-label="Switch panel" data-panel-switch>
            <a href="{{ route('dashboard') }}" class="sg-switch__link" @if ($panel === \App\Core\Navigation\MenuMode::Staff) aria-current="true" @endif>Staff</a>
            <a href="{{ route('admin.overview') }}" class="sg-switch__link" @if ($panel === \App\Core\Navigation\MenuMode::Admin) aria-current="true" @endif>Admin</a>
        </nav>
    @endif
    <x-shell.nav :menu="$menu" />
    <x-shell.user-dock :user="$user" :qa-enabled="$qaEnabled" />
</div>
