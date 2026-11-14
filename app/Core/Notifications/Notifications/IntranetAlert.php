<?php

namespace App\Core\Notifications\Notifications;

use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Generic intranet alert for push and/or the in-app bell.
 * Lock-screen wording must stay generic — never pass ticket subjects or bodies.
 */
class IntranetAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public NotificationCategory $category,
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $ticketReference = null,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return [];
        }

        $preferences = NotificationPreference::forUser($notifiable);
        $channels = [];

        if ($preferences->shouldSend($this->category, NotificationChannel::InApp)) {
            $channels[] = 'database';
        }

        if ($preferences->shouldSend($this->category, NotificationChannel::Push)
            && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * @return array{title: string, body: string, url: ?string, ticket_reference: ?string, category: string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'ticket_reference' => $this->ticketReference,
            'category' => $this->category->value,
        ];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->data(['url' => $this->url ?? '/'])
            ->options(['TTL' => 300]);
    }
}
