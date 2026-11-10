<x-layouts.app title="Who can see what" subtitle="How your Cloudron groups decide what you can see on the intranet.">
    <article class="sg-card max-w-3xl space-y-4 p-6">
        <h2 class="font-serif text-xl font-semibold">How access works</h2>
        <p>
            You sign in with your APES Cloudron account. Each time you sign in, the intranet looks at which of the
            8 approved Cloudron groups you are in, and gives you the matching role. Groups are changed in Cloudron
            by the APES IT Administrator, not on the intranet.
        </p>
        <p>
            Everyone has one <strong>account type</strong>, which says who you are. Some people also have a
            <strong>permission role</strong>, which says what extra they can do.
        </p>

        @foreach (['Account types' => $accountTypes, 'Permission roles' => $permissionRoles] as $heading => $rows)
            <h2 class="font-serif text-xl font-semibold">{{ $heading }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <caption class="sr-only">{{ $heading }}: the Cloudron group, the role and what it lets you do</caption>
                    <thead class="border-b border-sg-info-mist text-sm text-sg-muted">
                        <tr>
                            <th scope="col" class="py-2 pr-4">Role</th>
                            <th scope="col" class="py-2 pr-4">Cloudron group</th>
                            <th scope="col" class="py-2">What it lets you do</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-b border-sg-info-mist last:border-0 align-top">
                                <th scope="row" class="py-2 pr-4 font-semibold">{{ $row['role']->label() }}</th>
                                <td class="py-2 pr-4"><code class="text-sm">{{ $row['group'] }}</code></td>
                                <td class="py-2">{{ $row['role']->summary() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <h2 class="font-serif text-xl font-semibold">Who can see HR and legal records</h2>
        <ul class="list-disc space-y-1 pl-6">
            <li><strong>HR records</strong> can be seen by HR, management and admin.</li>
            <li><strong>Agreements and legal records</strong> can be seen by legal and admin.</li>
            <li>
                <strong>DBS checks and health information</strong> are special category data under UK GDPR. Only
                people who need them can open them.
            </li>
            <li>
                Before opening HR or agreements records, you are asked to sign in to Cloudron again if you last
                signed in more than {{ $freshSignInMinutes }} minutes ago. This applies to admin too.
            </li>
        </ul>

        <h2 class="font-serif text-xl font-semibold">Staff and Admin panels</h2>
        <p>
            People with the Admin role can switch the sidebar between the <strong>Staff</strong> panel (the member
            menu) and the <strong>Admin</strong> panel. Admin pages always show the Admin panel, and every other page
            shows the Staff panel. The switch is navigation only: it does not change your roles or permissions.
            HR and legal stay on the Staff menu. The local QA “Switch role” control is separate.
        </p>

        <h2 class="font-serif text-xl font-semibold">Access is audited</h2>
        <p>
            The intranet keeps an audit log. Every time anyone opens DBS or health information, and every time
            admin or management open HR or legal records, it is recorded with who, when and what. Admins and HR
            review group membership and this access every quarter.
        </p>

        <h2 class="font-serif text-xl font-semibold">If your access looks wrong</h2>
        <p>
            If you cannot see something you need, or can see something you should not, tell your manager or the
            APES IT Administrator. Changes take effect the next time you sign in.
        </p>
    </article>
</x-layouts.app>
