@props([
    'hasMenu' => false,
    'menu' => [],
    'panel' => null,
    'user' => null,
    'qaProfile' => null,
    'qaPicker' => null,
    'qaReturnTo' => null,
    'qaConfirm' => false,
    'unreadNotifications' => 0,
    'recentNotifications' => null,
])
<div class="sg-card sg-topbar">
    @if ($hasMenu)
        <button
            type="button"
            class="sg-icon-btn sg-topbar__menu-open"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="workspace-drawer"
            data-workspace-menu-open
        >
            <i data-lucide="menu"></i>
        </button>
    @endif
    <x-shell.brand :panel="$panel" />
    {{-- #322: Staff/Admin switcher moves beside the brand --}}
    @if ($hasMenu && count($menu) > 0)
        <x-shell.top-nav :menu="$menu" />
    @endif
    <div class="sg-topbar__actions">
        @auth
            <x-shell.notification-bell
                :unread-count="$unreadNotifications"
                :recent="$recentNotifications ?? collect()"
            />
            @if ($user)
                <x-shell.user-menu
                    :user="$user"
                    :qa-profile="$qaProfile"
                    :qa-picker="$qaPicker"
                    :qa-return-to="$qaReturnTo"
                    :qa-confirm="$qaConfirm"
                />
            @endif
        @endauth
    </div>
</div>
