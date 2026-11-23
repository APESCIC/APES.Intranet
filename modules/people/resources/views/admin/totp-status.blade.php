<x-layouts.app title="2FA status" subtitle="Read-only TOTP flags from Cloudron. Passkeys are not included.">
    <p class="mb-4 rounded-sg-field bg-sg-info-mist p-3 text-sg-ink" role="note">
        This page stores only a yes/no flag for authenticator-app (TOTP) set-up. The Cloudron API token stays in the server environment and is never shown here.
    </p>

    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <form method="post" action="{{ route('admin.totp.refresh') }}" class="mb-4">
        @csrf
        <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Refresh from Cloudron</button>
    </form>

    <p class="mb-3 text-sm text-sg-muted">Last checked: {{ $checkedAt?->toDayDateTimeString() ?: 'Never' }}</p>

    <x-admin.table-card>
        <table class="sg-table w-full text-left">
            <caption class="sr-only">People and whether Cloudron reports TOTP enabled</caption>
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">TOTP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($people as $person)
                    <tr>
                        <th scope="row">{{ $person->name }}</th>
                        <td>{{ $person->email }}</td>
                        <td>
                            @if ($person->totp_enabled === null)
                                <span class="sg-badge">Unknown</span>
                            @elseif ($person->totp_enabled)
                                <span class="sg-badge sg-badge--ok">On</span>
                            @else
                                <span class="sg-badge sg-badge--warn">Off</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <p class="sg-empty-state">No people to show yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.table-card>
</x-layouts.app>
