<x-layouts.app title="Privacy notice" subtitle="How the APES Intranet uses information about you.">
    <article class="sg-card max-w-3xl space-y-4 p-6">
        <p class="rounded-sg-field bg-sg-warn-mist p-3 text-sg-warn" role="note">
            <strong>Draft.</strong> This notice is a placeholder until APES CIC approves the final wording.
        </p>

        <h2 class="font-serif text-xl font-semibold">Who we are</h2>
        <p>
            The APES Intranet is run by the Association of Protecting Exotic Species CIC (APES CIC) for its staff,
            volunteers and students.
        </p>

        <h2 class="font-serif text-xl font-semibold">What we record</h2>
        <ul class="list-disc space-y-1 pl-6">
            <li>Your name, email address and intranet roles, taken from your APES Cloudron account when you sign in.</li>
            <li>
                An audit log of sign-ins, sign-outs, role changes and admin actions, with the time, your account, your
                IP address and your browser.
            </li>
        </ul>

        <h2 class="font-serif text-xl font-semibold">Why</h2>
        <p>To let you use the intranet, to keep it secure, and to show who did what if something goes wrong.</p>

        <h2 class="font-serif text-xl font-semibold">How long we keep it</h2>
        <p>
            Audit log entries are deleted automatically after {{ config('intranet.retention.audit_log_days') }} days.
        </p>

        <h2 class="font-serif text-xl font-semibold">Questions</h2>
        <p>Contact the APES CIC Director or the APES IT Administrator.</p>
    </article>
</x-layouts.app>
