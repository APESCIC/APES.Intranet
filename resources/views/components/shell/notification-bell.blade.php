@props(['unreadCount' => 0, 'recent' => collect()])

<div class="sg-notification-bell" data-notification-bell>
    <button
        type="button"
        class="sg-icon-btn"
        aria-label="Notifications{{ $unreadCount > 0 ? ', '.$unreadCount.' unread' : '' }}"
        aria-expanded="false"
        aria-controls="notification-bell-panel"
        data-notification-bell-toggle
    >
        <i data-lucide="bell"></i>
        @if ($unreadCount > 0)
            <span class="sg-notification-bell__badge" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div
        id="notification-bell-panel"
        class="sg-notification-bell__panel"
        role="region"
        aria-label="Recent notifications"
        hidden
        data-notification-bell-panel
    >
        <div class="sg-notification-bell__header">
            <p class="font-semibold">Notifications</p>
            @if ($unreadCount > 0)
                <form method="post" action="{{ route('notifications.mark-all-read') }}">
                    @csrf
                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Mark all read</button>
                </form>
            @endif
        </div>
        @if ($recent->isEmpty())
            <p class="sg-notification-bell__empty">No notifications yet.</p>
        @else
            <ul class="sg-notification-bell__list">
                @foreach ($recent as $item)
                    @php
                        $title = (string) data_get($item->data, 'title', 'Intranet notice');
                        $body = (string) data_get($item->data, 'body', '');
                        $reference = data_get($item->data, 'ticket_reference');
                    @endphp
                    <li @class(['sg-notification-bell__item', 'sg-notification-bell__item--unread' => $item->unread()])>
                        <form method="post" action="{{ route('notifications.read', $item) }}">
                            @csrf
                            <button type="submit" class="sg-notification-bell__link">
                                <span class="font-semibold">{{ $title }}</span>
                                @if (is_string($reference) && $reference !== '')
                                    <span class="block text-sm text-sg-muted">{{ $reference }}</span>
                                @endif
                                @if ($body !== '')
                                    <span class="block text-sm">{{ $body }}</span>
                                @endif
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
        <a class="sg-notification-bell__footer" href="{{ route('notifications.inbox') }}">View all notifications</a>
    </div>
</div>
