<?php

use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\Notifications\IntranetAlert;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('shows an accessible notification bell with an unread count', function () {
    $user = User::factory()->create()->assignRole('staff');
    $user->notify(new IntranetAlert(
        NotificationCategory::Tickets,
        'You have a new ticket reply',
        'Open the intranet to read it.',
        '/help-desk',
        'HD-1001',
    ));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-label="Notifications, 1 unread"', false)
        ->assertSee('HD-1001')
        ->assertSee('View all notifications')
        ->assertDontSee('Open the intranet to read it. and then a long reply body');
});

it('renders one top-bar bell with a unique popover panel id', function () {
    $html = $this->actingAs(User::factory()->create()->assignRole('staff'))
        ->get(route('dashboard'))
        ->assertOk()
        ->getContent();

    preg_match('/class="sg-topbar__actions"[^>]*>(.*?)<\/div>\s*<\/div>/s', $html, $topbarActions);

    expect(substr_count($html, 'id="notification-bell-panel"'))->toBe(1)
        ->and(preg_match_all('/\bdata-notification-bell\b(?!-)/', $html))->toBe(1)
        ->and(substr_count($html, 'data-notification-bell-toggle'))->toBe(1)
        ->and(substr_count($html, 'data-notification-bell-panel'))->toBe(1)
        ->and($html)->toContain('popover')
        ->and($html)->not->toContain('data-workspace-rail')
        ->and($topbarActions[1] ?? '')->toContain('data-notification-bell');
});

it('marks a notification as read when opened and follows a safe link', function () {
    $user = User::factory()->create()->assignRole('staff');
    $user->notify(new IntranetAlert(
        NotificationCategory::Announcements,
        'There is a new intranet announcement.',
        'Open the intranet to view it.',
        '/dashboard',
    ));

    $notification = $user->notifications()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->read_at)->toBeNull();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification))
        ->assertRedirect('/dashboard');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('lists notifications in the inbox', function () {
    $user = User::factory()->create()->assignRole('staff');
    Notification::send($user, new IntranetAlert(
        NotificationCategory::Operations,
        'You have a new rota or operations notice.',
        'Open the intranet to view it.',
    ));

    $this->actingAs($user)
        ->get(route('notifications.inbox'))
        ->assertOk()
        ->assertSee('You have a new rota or operations notice.');
});

it('does not put reply bodies into the in-app item', function () {
    $user = User::factory()->create()->assignRole('staff');
    $user->notify(new IntranetAlert(
        NotificationCategory::Tickets,
        'You have a new ticket reply',
        'Open the intranet to read it.',
        '/help-desk',
        'HD-42',
    ));

    $data = $user->notifications()->first()->data;

    expect($data['body'])->toBe('Open the intranet to read it.')
        ->and($data['body'])->not->toContain('student')
        ->and($data['ticket_reference'])->toBe('HD-42');
});
