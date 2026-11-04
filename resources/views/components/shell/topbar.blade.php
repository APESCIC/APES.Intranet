@props(['hasMenu' => false])
<div class="sg-card sg-topbar">
    @if ($hasMenu)
        <button
            type="button"
            class="sg-icon-btn"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="workspace-drawer"
            data-workspace-menu-open
        >
            <i data-lucide="menu"></i>
        </button>
    @endif
    <x-shell.brand />
</div>
