<x-layouts.app title="Audit log" :subtitle="'Sign-ins, role changes and admin actions. Read-only. Entries are kept for '.$retentionDays.' days.'">
    <div class="overflow-x-auto rounded-sg-card bg-sg-white shadow-sg-card">
        <table class="w-full text-left">
            <caption class="sr-only">Audit log entries, newest first</caption>
            <thead class="border-b border-sg-info-mist text-sm text-sg-muted">
                <tr>
                    <th scope="col" class="p-4">Time</th>
                    <th scope="col" class="p-4">Event</th>
                    <th scope="col" class="p-4">By</th>
                    <th scope="col" class="p-4">Details</th>
                    <th scope="col" class="p-4">IP address</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr class="border-b border-sg-info-mist last:border-0 align-top">
                        <td class="p-4 whitespace-nowrap">
                            <time datetime="{{ $entry->occurred_at->toIso8601String() }}">{{ $entry->occurred_at->timezone('Europe/London')->format('j M Y, H:i:s') }}</time>
                        </td>
                        <td class="p-4"><code>{{ $entry->event }}</code></td>
                        <td class="p-4">{{ $entry->actor?->name ?? 'Not signed in' }}</td>
                        <td class="p-4 text-sm">
                            @if ($entry->subject_type)
                                <span class="text-sg-muted">{{ class_basename($entry->subject_type) }} #{{ $entry->subject_id }}</span>
                            @endif
                            @foreach ($entry->context ?? [] as $key => $value)
                                <span class="block"><span class="text-sg-muted">{{ $key }}:</span> {{ is_array($value) ? (implode(', ', $value) ?: 'none') : $value }}</span>
                            @endforeach
                        </td>
                        <td class="p-4"><code>{{ $entry->ip_address }}</code></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-4 text-sg-muted">No entries yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $entries->links() }}
    </div>
</x-layouts.app>
