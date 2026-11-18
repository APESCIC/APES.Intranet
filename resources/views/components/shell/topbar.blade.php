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
    <x-shell.brand :panel="$panel" />
    @if ($hasMenu && count($menu) > 0)
        <x-shell.top-nav :menu="$menu" />
    @endif
    <div class="sg-topbar__actions">
        @auth
            <x-shell.notification-bell
                panel-id="notification-bell-panel"
                :unread-count="$unreadNotifications"
                :recent="$recentNotifications ?? collect()"
            />
            @if ($user)
                <x-shell.user-menu
                    :user="$user"
                    :panel="$panel"
                    :qa-profile="$qaProfile"
                    :qa-picker="$qaPicker"
                    :qa-return-to="$qaReturnTo"
                    :qa-confirm="$qaConfirm"
                />
            @endif
        @endauth
    </div>
</div>
