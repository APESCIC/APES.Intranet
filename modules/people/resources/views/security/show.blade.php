<x-layouts.app title="Security" subtitle="Manage passkeys, TOTP, backup codes, password and sessions in Cloudron.">
    <article class="sg-card max-w-3xl space-y-4 p-6">
        <p>
            The intranet never stores passkeys, TOTP secrets or backup codes. Open your Cloudron profile to manage them.
        </p>
        <a class="sg-btn sg-btn-primary" href="{{ $profileUrl }}" target="_blank" rel="noopener noreferrer">
            Open Cloudron profile<span class="sr-only"> (opens in a new tab)</span>
        </a>

        <h2 class="font-serif text-xl font-semibold">What you can do there</h2>
        <ol class="list-decimal space-y-2 pl-6">
            <li>Add or remove a passkey.</li>
            <li>Turn on or off an authenticator app (TOTP).</li>
            <li>View or regenerate backup codes.</li>
            <li>Change your password.</li>
            <li>Create app passwords if you need them.</li>
            <li>Review and end login sessions.</li>
        </ol>

        <h2 class="font-serif text-xl font-semibold">Handbook guidance</h2>
        <p>
            Read the
            <a class="underline" href="{{ route('communications.handbook.show', 'sign-in-and-2fa') }}">sign-in methods and 2FA handbook chapter</a>
            for plain-English help, including options without a smartphone.
        </p>
    </article>
</x-layouts.app>
