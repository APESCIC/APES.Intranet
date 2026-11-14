@props(['user', 'qaProfile' => null, 'qaPicker' => null, 'qaReturnTo' => null, 'qaConfirm' => false])
<div class="sg-dock" data-qa-dock>
    <div class="sg-dock__card">
        <span class="sg-avatar" aria-hidden="true">{{ $user->initials() }}</span>
        <span class="sg-dock__identity">
            <span class="sg-dock__name">{{ $user->name }}</span>
            <span class="sg-dock__role">{{ $user->primaryRoleLabel() ?? 'No intranet role' }}</span>
        </span>
    </div>
    @if ($qaProfile)
        <button type="button" class="sg-qa-pill" popovertarget="qa-switcher" aria-expanded="false" data-qa-pill>
            <i data-lucide="flask-conical"></i>
            <span class="sg-qa-pill__label">QA: {{ \App\Core\Qa\QaLogin::label($qaProfile) }}</span>
        </button>
        <div
            id="qa-switcher"
            class="sg-qa-flyout"
            popover
            role="dialog"
            aria-labelledby="qa-switcher-heading"
            data-qa-flyout
            @if ($qaConfirm) data-qa-autoopen @endif
        >
            <div class="sg-qa-panel__header">
                <h2 id="qa-switcher-heading" class="sg-qa-panel__title">Switch QA role</h2>
                <span class="sg-qa-tag">Local only</span>
            </div>
            <x-qa.profile-picker :profiles="$qaPicker" :current="$qaProfile" :return-to="$qaReturnTo" id-prefix="qa-switcher" />
            <form method="post" action="{{ route('qa.logout') }}" class="sg-qa-flyout__signout">
                @csrf
                <button type="submit" class="sg-btn sg-btn-secondary">
                    <i data-lucide="log-out"></i>
                    Sign out of QA
                </button>
            </form>
        </div>
    @elseif (Route::has('logout'))
        <div class="sg-dock__actions">
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">
                    <i data-lucide="log-out"></i>
                    Sign out
                </button>
            </form>
        </div>
    @endif
</div>
