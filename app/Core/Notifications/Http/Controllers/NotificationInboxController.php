<?php

namespace App\Core\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationInboxController
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('core.notifications.inbox', [
            'notifications' => $user->notifications()->latest()->limit(50)->get(),
        ]);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var DatabaseNotification|null $item */
        $item = $user->notifications()->whereKey($notification)->first();

        if ($item === null) {
            abort(404);
        }

        $item->markAsRead();

        $url = data_get($item->data, 'url');
        if (is_string($url) && $url !== '' && str_starts_with($url, '/')) {
            return redirect()->to($url);
        }

        return redirect()->route('notifications.inbox');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return redirect()
            ->back(fallback: route('notifications.inbox'))
            ->with('status', 'All notifications marked as read.');
    }
}
