<?php

namespace App\Core\Notifications\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;
use NotificationChannels\WebPush\PushSubscription;

/**
 * Admin counts for Web Push health. Never shows payloads or full endpoints.
 */
class PushDeliveryStatsController
{
    public function __invoke(AuditLogger $audit): View
    {
        $audit->record('admin.push_delivery.viewed');

        $subscriptionCount = PushSubscription::query()->count();
        $subscriberCount = PushSubscription::query()
            ->pluck('subscribable_id')
            ->unique()
            ->count();

        $preferences = NotificationPreference::query()->get();
        $optInByCategory = [];
        foreach (NotificationCategory::ordered() as $category) {
            $optInByCategory[$category->value] = [
                'label' => $category->label(),
                'push' => $preferences->filter(
                    fn (NotificationPreference $preference) => $preference->allows($category, NotificationChannel::Push),
                )->count(),
            ];
        }

        $inAppTotal = DatabaseNotification::query()->count();
        $inAppUnread = DatabaseNotification::query()->whereNull('read_at')->count();

        return view('core.admin.notifications.push-delivery', [
            'vapidConfigured' => filled(config('webpush.vapid.public_key'))
                && filled(config('webpush.vapid.private_key')),
            'subscriptionCount' => $subscriptionCount,
            'subscriberCount' => $subscriberCount,
            'optInByCategory' => $optInByCategory,
            'inAppTotal' => $inAppTotal,
            'inAppUnread' => $inAppUnread,
        ]);
    }
}
