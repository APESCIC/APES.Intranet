<x-layouts.app title="Notifications" subtitle="Alerts that stay in the intranet when push is off or unavailable.">
    @if (session('status'))
        <p class="mb-4 max-w-3xl rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <div class="mb-4 flex flex-wrap gap-3">
        <a href="{{ route('notifications.preferences.edit') }}" class="sg-btn sg-btn-secondary">Preferences</a>
        @if ($notifications->contains(fn ($n) => $n->unread()))
            <form method="post" action="{{ route('notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="sg-btn sg-btn-secondary">Mark all read</button>
            </form>
        @endif
    </div>

    <div class="sg-card max-w-3xl p-6">
        @if ($notifications->isEmpty())
            <p class="text-sg-muted">No notifications yet.</p>
        @else
            <ul class="space-y-3">
                @foreach ($notifications as $item)
                    @php
                        $title = (string) data_get($item->data, 'title', 'Intranet notice');
                        $body = (string) data_get($item->data, 'body', '');
                        $reference = data_get($item->data, 'ticket_reference');
                    @endphp
                    <li @class(['rounded-sg-field border border-sg-info-mist p-3', 'bg-sg-info-mist/40' => $item->unread()])>
                        <form method="post" action="{{ route('notifications.read', $item) }}">
                            @csrf
                            <button type="submit" class="w-full text-left">
                                <span class="font-semibold">{{ $title }}</span>
                                @if (is_string($reference) && $reference !== '')
                                    <span class="mt-1 block text-sm text-sg-muted">{{ $reference }}</span>
                                @endif
                                @if ($body !== '')
                                    <span class="mt-1 block text-sm">{{ $body }}</span>
                                @endif
                                <span class="mt-2 block text-xs text-sg-muted">{{ $item->created_at?->timezone('Europe/London')->format('j M Y, H:i') }}</span>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.app>
