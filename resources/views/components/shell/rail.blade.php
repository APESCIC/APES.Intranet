@props([
    'menu',
    'user',
    'panel' => null,
    'qaProfile' => null,
    'qaPicker' => null,
    'qaReturnTo' => null,
    'qaConfirm' => false,
    'unreadNotifications' => 0,
    'recentNotifications' => null,
])
<div class="sg-rail" data-workspace-rail>
    <div class="sg-rail__top">
        <x-shell.brand :panel="$panel" />
        <div class="sg-rail__top-actions">
            <x-shell.notification-bell
                :unread-count="$unreadNotifications"
                :recent="$recentNotifications ?? collect()"
            />
            <button type="button" class="sg-icon-btn" aria-label="Close menu" data-workspace-close hidden>
                <i data-lucide="x"></i>
            </button>
        </div>
    </div>
    <x-shell.panel-switch :panel="$panel" class="sg-switch--drawer" />
    <x-shell.nav :menu="$menu" />
    <x-shell.user-dock
        :user="$user"
        :qa-profile="$qaProfile"
        :qa-picker="$qaPicker"
        :qa-return-to="$qaReturnTo"
        :qa-confirm="$qaConfirm"
    />
</div>
