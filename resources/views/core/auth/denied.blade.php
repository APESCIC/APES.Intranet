<x-layouts.app title="No intranet access">
    <section class="sg-card max-w-xl p-6" aria-labelledby="denied-heading">
        <h2 id="denied-heading" class="font-serif text-xl font-semibold">You are signed in to Cloudron, but not to the intranet</h2>
        <p class="mt-2">
            {{ $reason ?? 'Your account is not in an intranet group yet. Staff, volunteers and students need to be added to an intranet group in the APES directory.' }}
        </p>
        <p class="mt-2">
            Ask your manager or the APES IT Administrator to give you access, then sign in again.
        </p>
        <a href="{{ route('login') }}" class="sg-btn sg-btn-secondary mt-4">Back to sign in</a>
    </section>
</x-layouts.app>
