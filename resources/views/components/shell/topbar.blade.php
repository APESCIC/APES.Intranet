@props(['hasMenu' => false, 'panel' => null, 'unreadNotifications' => 0, 'recentNotifications' => null])
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
    <x-shell.brand :panel="$panel" />
    <div class="sg-topbar__actions">
        @auth
            <x-shell.notification-bell
                :unread-count="$unreadNotifications"
                :recent="$recentNotifications ?? collect()"
            />
        @endauth
    </div>
</div>
