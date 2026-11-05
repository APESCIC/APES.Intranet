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

    <div class="overflow-x-auto rounded-sg-card bg-sg-white shadow-sg-card">
        <table class="w-full text-left">
            <caption class="sr-only">People and whether Cloudron reports TOTP enabled</caption>
            <thead class="border-b border-sg-info-mist text-sm text-sg-muted">
                <tr>
                    <th scope="col" class="p-4">Name</th>
                    <th scope="col" class="p-4">Email</th>
                    <th scope="col" class="p-4">TOTP</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($people as $person)
                    <tr class="border-b border-sg-info-mist last:border-0">
                        <th scope="row" class="p-4 font-semibold">{{ $person->name }}</th>
                        <td class="p-4">{{ $person->email }}</td>
                        <td class="p-4">
                            @if ($person->totp_enabled === null)
                                Unknown
                            @elseif ($person->totp_enabled)
                                On
                            @else
                                Off
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
