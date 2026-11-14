<x-layouts.app title="Sign in" subtitle="Use your APES Cloudron account, the same one you use for email and the LMS.">
    @if (session('status'))
        <p class="sg-card mb-4 p-4" role="status">{{ session('status') }}</p>
    @endif

    <section class="sg-card max-w-xl p-6" aria-labelledby="sign-in-heading">
        <h2 id="sign-in-heading" class="font-serif text-xl font-semibold">Staff, volunteers and students</h2>
        <p class="mt-2 text-sg-muted">
            You will go to the APES sign-in page, then come back here.
        </p>
        <a href="{{ route('auth.redirect') }}" class="sg-btn sg-btn-primary mt-4">
            <i data-lucide="log-in"></i>
            Sign in with Cloudron
        </a>
    </section>

    @if ($qaProfiles)
        <x-qa.signin-panel :profiles="$qaProfiles" class="mt-6" />
    @endif
</x-layouts.app>
