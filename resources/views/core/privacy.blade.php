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
                Profile details you choose to share (team, skills, contact details, photo) and, for HR and management,
                emergency contact details and employee number.
            </li>
            <li>
                A yes/no flag for whether Cloudron reports that you have authenticator-app (TOTP) two-factor
                authentication turned on. Passkeys and secrets are never stored here.
            </li>
            <li>
                Operations records: your rota shifts, times you say you cannot work, claims and swaps, clock-in,
                break and clock-out times, timesheets, tasks, form submissions and checklists, including any photos or
                signatures you add to them.
            </li>
            <li>
                Only if APES CIC has approved and switched on the clock-in location check for a site: whether your
                device was near that site when you tapped Clock in (near or not near). Your location itself is never
                saved, and nothing is checked at any other time.
            </li>
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
