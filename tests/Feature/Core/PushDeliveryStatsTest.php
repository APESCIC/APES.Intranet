<?php

use App\Core\Access\RoleName;
use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use App\Core\Notifications\Notifications\IntranetAlert;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows push delivery counts to admins without endpoints or payloads', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);

    // In-app item first (no subscription yet, so via() stays on the database channel).
    $member->notify(new IntranetAlert(
        NotificationCategory::Tickets,
        'You have a new ticket reply.',
        'Open the intranet to read it.',
        '/help-desk',
        'HD-99',
    ));

    $member->updatePushSubscription(
        'https://push.example.test/endpoint/secret-device-token',
        'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
        'tBHIQiVF51eXQuPTqKwK4g',
        'aes128gcm',
    );

    $preferences = NotificationPreference::forUser($member);
    $channels = $preferences->channels;
    $channels[NotificationCategory::Tickets->value][NotificationChannel::Push->value] = true;
    $preferences->update(['channels' => $channels]);

    $this->actingAs($admin)
        ->withSession(['intranet.auth_time' => now()->getTimestamp()])
        ->get(route('admin.push-delivery.index'))
        ->assertOk()
        ->assertSee('Push delivery')
        ->assertSee('Stored subscriptions')
        ->assertSee('>1</dd>', false)
        ->assertDontSee('secret-device-token')
        ->assertDontSee('https://push.example.test')
        ->assertDontSee('You have a new ticket reply.')
        ->assertDontSee('HD-99')
        ->assertDontSee((string) config('webpush.vapid.private_key'));
});

it('forbids staff from the push delivery page', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)
        ->get(route('admin.push-delivery.index'))
        ->assertForbidden();
});
