<?php

namespace App\Core\Notifications\Http\Controllers;

use App\Core\Notifications\Notifications\TestPushNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TestPushNotificationController
{
    public function __invoke(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->pushSubscriptions()->doesntExist()) {
            return redirect()
                ->route('notifications.preferences.edit')
                ->with('error', 'Turn on browser notifications and allow this device first, then try the test again.');
        }

        if (! filled(config('webpush.vapid.public_key')) || ! filled(config('webpush.vapid.private_key'))) {
            return redirect()
                ->route('notifications.preferences.edit')
                ->with('error', 'Web Push is not configured on this server yet.');
        }

        Notification::send($user, new TestPushNotification);

        return redirect()
            ->route('notifications.preferences.edit')
            ->with('status', 'A test notification has been queued for this device.');
    }
}
