<?php

namespace App\Core\Notifications\Http\Controllers;

use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationPreferencesController
{
    public function edit(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $preferences = NotificationPreference::forUser($user);

        return view('core.notifications.preferences', [
            'preferences' => $preferences,
            'categories' => NotificationCategory::ordered(),
            'channels' => NotificationChannel::cases(),
            'subscriptions' => $user->pushSubscriptions()->latest()->get(),
            'pushConfigured' => filled(config('webpush.vapid.public_key')),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $preferences = NotificationPreference::forUser($user);

        $rules = [
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i', 'required_with:quiet_hours_start'],
        ];
        foreach (NotificationCategory::ordered() as $category) {
            foreach (NotificationChannel::cases() as $channel) {
                $rules["channels.{$category->value}.{$channel->value}"] = ['sometimes', 'boolean'];
            }
        }

        $validated = $request->validate($rules);

        $channels = NotificationPreference::defaultChannels();
        foreach (NotificationCategory::ordered() as $category) {
            foreach (NotificationChannel::cases() as $channel) {
                $channels[$category->value][$channel->value] = $request->boolean(
                    "channels.{$category->value}.{$channel->value}"
                );
            }
        }

        $preferences->fill([
            'channels' => $channels,
            'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
            'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
        ])->save();

        return redirect()
            ->route('notifications.preferences.edit')
            ->with('status', 'Your notification preferences have been saved.');
    }
}
