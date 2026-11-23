@props([
    'user',
    'panel' => null,
    'qaProfile' => null,
    'qaPicker' => null,
    'qaReturnTo' => null,
    'qaConfirm' => false,
])
@php
    $panelId = 'user-account-menu-panel';
@endphp
<div class="sg-user-menu" data-workspace-user-menu>
    <button
        type="button"
        class="sg-user-menu__trigger"
        aria-expanded="false"
        aria-controls="{{ $panelId }}"
        aria-haspopup="true"
        data-workspace-user-menu-trigger
    >
        <span class="sg-avatar" aria-hidden="true">{{ $user->initials() }}</span>
        <span class="sr-only">Account menu for {{ $user->name }}</span>
    </button>
    <div
        id="{{ $panelId }}"
        class="sg-user-menu__panel"
        hidden
        role="region"
        aria-label="Account menu"
        data-workspace-user-menu-panel
    >
        <div class="sg-user-menu__identity">
            <span class="sg-user-menu__name">{{ $user->name }}</span>
            <span class="sg-user-menu__role">{{ $user->primaryRoleLabel() ?? 'No intranet role' }}</span>
        </div>
        @if ($panel)
            <nav class="sg-user-menu__panels" aria-label="Switch panel" data-panel-switch>
                <a href="{{ route('dashboard') }}" class="sg-nav-link" data-nav-key="staff-panel" @if ($panel === \App\Core\Navigation\MenuMode::Staff) aria-current="true" @endif>
                    <i data-lucide="layout-dashboard"></i>
                    <span>Intranet</span>
                </a>
                <a href="{{ route('admin.overview') }}" class="sg-nav-link" data-nav-key="admin-panel" @if ($panel === \App\Core\Navigation\MenuMode::Admin) aria-current="true" @endif>
                    <i data-lucide="monitor-cog"></i>
                    <span>Admin panel</span>
                </a>
            </nav>
        @endif
        <section class="sg-user-menu__appearance" data-appearance-panel aria-label="Appearance">
            <p class="sg-user-menu__appearance-heading">Appearance</p>
            <fieldset class="sg-appearance-fieldset">
                <legend class="sr-only">Colour mode</legend>
                <div class="sg-appearance-options" role="radiogroup" aria-label="Colour mode">
                    <label class="sg-appearance-option">
                        <input type="radio" name="appearance-mode" value="light" data-appearance-mode-option>
                        <span>Light</span>
                    </label>
                    <label class="sg-appearance-option">
                        <input type="radio" name="appearance-mode" value="dark" data-appearance-mode-option>
                        <span>Dark</span>
                    </label>
                    <label class="sg-appearance-option">
                        <input type="radio" name="appearance-mode" value="system" data-appearance-mode-option>
                        <span>System</span>
                    </label>
                </div>
            </fieldset>
            <fieldset class="sg-appearance-fieldset">
                <legend class="sr-only">Theme</legend>
                <div class="sg-appearance-options" role="radiogroup" aria-label="Theme">
                    <label class="sg-appearance-option">
                        <input type="radio" name="appearance-theme" value="glass" data-appearance-theme>
                        <span>Glass</span>
                    </label>
                    <label class="sg-appearance-option">
                        <input type="radio" name="appearance-theme" value="jungle" data-appearance-theme>
                        <span>Jungle</span>
                    </label>
                </div>
            </fieldset>
            <label class="sg-appearance-check">
                <input type="checkbox" data-appearance-reduced-transparency>
                <span>Reduced transparency</span>
            </label>
        </section>
        <ul class="sg-user-menu__list">
            <li>
                <a href="{{ route('people.profile.edit') }}" class="sg-nav-link" data-nav-key="profile" @if (request()->routeIs('people.profile.*')) aria-current="page" @endif>
                    <i data-lucide="circle-user"></i>
                    <span>My profile</span>
                </a>
            </li>
            <li>
                <a href="{{ route('notifications.preferences.edit') }}" class="sg-nav-link" data-nav-key="notifications" @if (request()->routeIs('notifications.preferences.*')) aria-current="page" @endif>
                    <i data-lucide="bell"></i>
                    <span>Notification settings</span>
                </a>
            </li>
            <li>
                <a href="{{ route('people.security') }}" class="sg-nav-link" data-nav-key="security" @if (request()->routeIs('people.security')) aria-current="page" @endif>
                    <i data-lucide="shield"></i>
                    <span>Security</span>
                </a>
            </li>
        </ul>
        @if ($qaProfile)
            <div class="sg-user-menu__qa" data-qa-dock>
                <button type="button" class="sg-qa-pill" popovertarget="qa-switcher-user-menu" aria-expanded="false" data-qa-pill>
                    <i data-lucide="flask-conical"></i>
                    <span class="sg-qa-pill__label">QA: {{ \App\Core\Qa\QaLogin::label($qaProfile) }}</span>
                </button>
                <div
                    id="qa-switcher-user-menu"
                    class="sg-qa-flyout"
                    popover
                    role="dialog"
                    aria-labelledby="qa-switcher-user-menu-heading"
                    data-qa-flyout
                    @if ($qaConfirm) data-qa-autoopen @endif
                >
                    <div class="sg-qa-panel__header">
                        <h2 id="qa-switcher-user-menu-heading" class="sg-qa-panel__title">Switch QA role</h2>
                        <span class="sg-qa-tag">Local only</span>
                    </div>
                    <x-qa.profile-picker :profiles="$qaPicker" :current="$qaProfile" :return-to="$qaReturnTo" id-prefix="qa-switcher-user-menu" />
                    <form method="post" action="{{ route('qa.logout') }}" class="sg-qa-flyout__signout">
                        @csrf
                        <button type="submit" class="sg-btn sg-btn-secondary">
                            <i data-lucide="log-out"></i>
                            Sign out of QA
                        </button>
                    </form>
                </div>
            </div>
        @elseif (Route::has('logout'))
            <form method="post" action="{{ route('logout') }}" class="sg-user-menu__signout">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">
                    <i data-lucide="log-out"></i>
                    Sign out
                </button>
            </form>
        @endif
    </div>
</div>
