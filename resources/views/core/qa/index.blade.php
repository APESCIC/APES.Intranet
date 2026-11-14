<x-layouts.app title="QA role switcher" subtitle="Local only. The same Developer sign-in panel as the login page.">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-warn-mist p-3 text-sg-warn" role="status">{{ session('status') }}</p>
    @endif
    @if ($current)
        <p class="mb-4">
            Signed in as <strong>{{ $current->name }}</strong> ({{ $current->email }}).
        </p>
    @endif

    <x-qa.signin-panel :profiles="$qaProfiles" />

    @if ($current)
        <form method="post" action="{{ route('qa.logout') }}" class="mt-6">
            @csrf
            <button type="submit" class="sg-btn sg-btn-secondary">
                <i data-lucide="log-out"></i>
                Sign out of QA
            </button>
        </form>
    @endif
</x-layouts.app>
