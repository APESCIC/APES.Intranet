<?php

namespace App\Core\Notifications\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Store and remove browser push subscriptions for the signed-in person.
 */
class PushSubscriptionController
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:1024', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
            $validated['contentEncoding'] ?? null,
        );

        return response()->json(['status' => 'subscribed'], 201);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:1024', 'url'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->deletePushSubscription($validated['endpoint']);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'unsubscribed']);
        }

        return redirect()
            ->route('notifications.preferences.edit')
            ->with('status', 'That device has been removed.');
    }
}
