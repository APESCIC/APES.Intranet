<x-layouts.app title="Accept Terms of use" :subtitle="$summary">
    <article class="sg-card max-w-3xl space-y-6 p-6">
        <p class="text-sm text-sg-muted">
            Version {{ $version->number }}
            @if ($version->in_effect_at)
                · In effect from {{ $version->in_effect_at->timezone(config('app.timezone'))->format('j F Y') }}
            @else
                · In effect now
            @endif
        </p>

        <p>
            You need to accept the current Terms of use before you continue using the intranet.
            You can read the full text, the Privacy notice and sign out if you prefer.
        </p>

        <ul class="list-disc space-y-1 pl-5 text-sm">
            <li><a class="underline" href="{{ route('legal.terms') }}">Read the Terms of use</a></li>
            <li><a class="underline" href="{{ route('privacy') }}">Privacy notice</a></li>
            <li><a class="underline" href="{{ route('legal.cookies') }}">Cookies</a></li>
        </ul>

        <form method="post" action="{{ route('legal.accept.store') }}" class="flex flex-wrap items-center gap-3">
            @csrf
            <button type="submit" class="sg-button sg-button--primary">Accept Terms of use</button>
        </form>

        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm underline">Sign out</button>
        </form>
    </article>
</x-layouts.app>