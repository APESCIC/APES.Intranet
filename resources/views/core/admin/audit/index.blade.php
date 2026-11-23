<x-layouts.app title="Audit log" :subtitle="'Sign-ins, role changes and admin actions. Read-only. Entries are kept for '.$retentionDays.' days.'">
    <x-admin.table-card>
        <table class="sg-table w-full text-left">
            <caption class="sr-only">Audit log entries, newest first</caption>
            <thead>
                <tr>
                    <th scope="col">Time</th>
                    <th scope="col">Event</th>
                    <th scope="col">By</th>
                    <th scope="col">Details</th>
                    <th scope="col">IP address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="align-top">
                        <td class="whitespace-nowrap">
                            <time datetime="{{ $entry->occurred_at->toIso8601String() }}">{{ $entry->occurred_at->timezone('Europe/London')->format('j M Y, H:i:s') }}</time>
                        </td>
                        <td><code>{{ $entry->event }}</code></td>
                        <td>{{ $entry->actor?->name ?? 'Not signed in' }}</td>
                        <td class="text-sm">
                            @if ($entry->subject_type)
                                <span class="text-sg-muted">{{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}</span>
                            @endif
                            @foreach ($entry->context ?? [] as $key => $value)
                                <span class="block"><span class="text-sg-muted">{{ $key }}:</span> {{ is_array($value) ? (implode(', ', $value) ?: 'none') : $value }}</span>
                            @endforeach
                        </td>
                        <td><code>{{ $entry->ip_address }}</code></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <p class="sg-empty-state">No entries yet.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-admin.table-card>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</x-layouts.app>
