@props(['user', 'qaEnabled' => false])
<div class="sg-dock">
    <div class="sg-dock__card">
        <span class="sg-avatar" aria-hidden="true">{{ $user->initials() }}</span>
        <span class="sg-dock__identity">
            <span class="sg-dock__name">{{ $user->name }}</span>
            <span class="sg-dock__role">{{ $user->primaryRoleLabel() ?? 'No intranet role' }}</span>
        </span>
    </div>
    <div class="sg-dock__actions">
        @if (Route::has('logout'))
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">
                    <i data-lucide="log-out"></i>
                    Sign out
                </button>
            </form>
        @elseif ($qaEnabled)
            <form method="post" action="{{ route('qa.logout') }}">
                @csrf
                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">
                    <i data-lucide="log-out"></i>
                    Sign out
                </button>
            </form>
        @endif
        @if ($qaEnabled)
            <a href="{{ route('qa.index') }}" class="sg-btn sg-btn-ghost sg-btn-sm">
                <i data-lucide="flask-conical"></i>
                Switch role
            </a>
        @endif
    </div>
</div>
