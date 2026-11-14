<?php

use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows notification preferences with push off by default', function () {
    $user = User::factory()->create()->assignRole('staff');

    $this->actingAs($user)
        ->get(route('notifications.preferences.edit'))
        ->assertOk()
        ->assertSee('Notification preferences')
        ->assertSee('Turn on browser notifications')
        ->assertSee('Shared devices')
        ->assertSee('iPhone and iPad');

    $preferences = NotificationPreference::forUser($user);

    expect($preferences->allows(NotificationCategory::Tickets, NotificationChannel::Push))->toBeFalse()
        ->and($preferences->allows(NotificationCategory::Tickets, NotificationChannel::Email))->toBeTrue()
        ->and($preferences->allows(NotificationCategory::Tickets, NotificationChannel::InApp))->toBeTrue();
});

it('saves independent category and channel choices with quiet hours', function () {
    $user = User::factory()->create()->assignRole('staff');

    $this->actingAs($user)
        ->put(route('notifications.preferences.update'), [
            'channels' => [
                'tickets' => ['push' => '1', 'email' => '1', 'in_app' => '1'],
                'account_security' => ['email' => '1', 'in_app' => '1'],
                'announcements' => ['in_app' => '1'],
                'operations' => ['email' => '1'],
            ],
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ])
        ->assertRedirect(route('notifications.preferences.edit'));

    $preferences = NotificationPreference::forUser($user->fresh());

    expect($preferences->allows(NotificationCategory::Tickets, NotificationChannel::Push))->toBeTrue()
        ->and($preferences->allows(NotificationCategory::Announcements, NotificationChannel::Push))->toBeFalse()
        ->and($preferences->allows(NotificationCategory::Announcements, NotificationChannel::Email))->toBeFalse()
        ->and($preferences->quiet_hours_start->format('H:i'))->toBe('22:00')
        ->and($preferences->quiet_hours_end->format('H:i'))->toBe('07:00');
});

it('blocks ordinary push during quiet hours but allows account and security', function () {
    $user = User::factory()->create()->assignRole('staff');
    $preferences = NotificationPreference::forUser($user);
    $preferences->update([
        'channels' => [
            'tickets' => ['push' => true, 'email' => true, 'in_app' => true],
            'account_security' => ['push' => true, 'email' => true, 'in_app' => true],
            'announcements' => ['push' => false, 'email' => true, 'in_app' => true],
            'operations' => ['push' => false, 'email' => true, 'in_app' => true],
        ],
        'quiet_hours_start' => '22:00',
        'quiet_hours_end' => '07:00',
    ]);

    $night = Carbon::parse('2026-11-14 23:30:00', 'Europe/London');

    expect($preferences->shouldSend(NotificationCategory::Tickets, NotificationChannel::Push, $night))->toBeFalse()
        ->and($preferences->shouldSend(NotificationCategory::AccountSecurity, NotificationChannel::Push, $night))->toBeTrue();
});

it('removes a device from the preferences page', function () {
    $user = User::factory()->create()->assignRole('staff');
    $user->updatePushSubscription(
        'https://push.example.test/device/1',
        'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
        'tBHIQiVF51eXQuPTqKwK4g',
        'aes128gcm',
    );

    $this->actingAs($user)
        ->from(route('notifications.preferences.edit'))
        ->delete(route('push-subscriptions.destroy'), [
            'endpoint' => 'https://push.example.test/device/1',
        ])
        ->assertRedirect(route('notifications.preferences.edit'));

    expect($user->fresh()->pushSubscriptions)->toHaveCount(0);
});

it('mentions push endpoints and generic wording on the privacy notice', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertSee('push subscription endpoint')
        ->assertSee('Lock-screen wording stays generic');
});
