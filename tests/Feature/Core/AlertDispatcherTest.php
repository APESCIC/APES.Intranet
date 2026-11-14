<?php

use App\Core\Access\RoleName;
use App\Core\Notifications\NotificationCategory;
use App\Core\Notifications\NotificationChannel;
use App\Core\Notifications\NotificationPreference;
use App\Core\Notifications\Notifications\IntranetAlert;
use App\Core\System\SystemStatus;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Modules\Communications\Http\Controllers\EventController;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\EventRsvp;
use Modules\Helpdesk\Services\TicketMailer;
use Modules\Operations\Support\Notifier;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\Fakes\FakeAddonCheck;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    Helpdesk::seed();
    $this->seed(CommunicationsPermissionsSeeder::class);
});

function enableCategoryPush(User $user, NotificationCategory $category): void
{
    $preferences = NotificationPreference::forUser($user);
    $channels = $preferences->channels;
    $channels[$category->value][NotificationChannel::Push->value] = true;
    $preferences->update(['channels' => $channels]);
}

function subscribeUser(User $user, string $endpoint = 'https://push.example.test/endpoint/alert'): void
{
    $user->updatePushSubscription(
        $endpoint,
        'BNcRdreALSVUFxm0i0YCnymtfXEEXVxqRnPoQpN9xq-bGmpb6S0u8r3OQKQKQYKd',
        'tBHIQiVF51eXQuPTqKwK4g',
        'aes128gcm',
    );
}

it('sends a generic ticket alert when a ticket is assigned', function () {
    Notification::fake();
    Mail::fake();

    $actor = Helpdesk::member(RoleName::Admin);
    $assignee = Helpdesk::member(RoleName::Staff, [
        'name' => 'Alex Assignee',
        'email' => 'assignee@example.test',
    ]);
    enableCategoryPush($assignee, NotificationCategory::Tickets);
    subscribeUser($assignee);

    $ticket = Helpdesk::ticket($actor, attributes: [
        'subject' => 'Student safeguarding concern about Jamie',
        'assignee_id' => null,
    ]);

    app(TicketMailer::class)->assigned($ticket, $assignee, $actor);

    Notification::assertSentTo($assignee, IntranetAlert::class, function (IntranetAlert $alert) use ($ticket, $assignee) {
        $payload = $alert->toArray($assignee);
        $push = $alert->toWebPush($assignee, $alert);

        expect($alert->category)->toBe(NotificationCategory::Tickets)
            ->and($alert->title)->toBe('A ticket has been assigned to you.')
            ->and($alert->body)->toBe('Open the intranet to view it.')
            ->and($alert->ticketReference)->toBe($ticket->reference())
            ->and($payload['title'])->not->toContain('Jamie')
            ->and($payload['title'])->not->toContain('safeguarding')
            ->and($payload['body'])->not->toContain('Jamie')
            ->and(json_encode($push))->not->toContain('Jamie')
            ->and(json_encode($push))->not->toContain('safeguarding')
            ->and($alert->via($assignee))->toContain(WebPushChannel::class)
            ->and($alert->via($assignee))->toContain('database');

        return true;
    });
});

it('keeps student ticket push wording generic', function () {
    Notification::fake();
    Mail::fake();

    $student = Helpdesk::member(RoleName::Student, [
        'name' => 'Student Sam',
        'email' => 'student@example.test',
    ]);
    $agent = Helpdesk::member(RoleName::Staff, [
        'name' => 'Help Agent',
        'email' => 'agent@example.test',
    ]);
    enableCategoryPush($agent, NotificationCategory::Tickets);
    subscribeUser($agent);

    $ticket = Helpdesk::ticket($student, attributes: [
        'subject' => 'I need help with my coursework deadline',
        'assignee_id' => $agent->id,
    ]);

    app(TicketMailer::class)->replied(
        $ticket,
        $ticket->messages()->create([
            'author_id' => $student->id,
            'body' => '<p>My personal health details are in this reply.</p>',
            'is_private' => false,
        ]),
        $student,
    );

    Notification::assertSentTo($agent, IntranetAlert::class, function (IntranetAlert $alert) {
        expect($alert->title)->toBe('You have a new ticket reply.')
            ->and($alert->body)->toBe('Open the intranet to read it.')
            ->and($alert->body)->not->toContain('health')
            ->and($alert->title)->not->toContain('coursework')
            ->and($alert->title)->not->toContain('Student Sam');

        return true;
    });
});

it('does not use the push channel when the category is off', function () {
    $user = Helpdesk::member();
    subscribeUser($user);
    $preferences = NotificationPreference::forUser($user);
    expect($preferences->allows(NotificationCategory::Tickets, NotificationChannel::Push))->toBeFalse();

    $alert = new IntranetAlert(
        NotificationCategory::Tickets,
        'You have a new ticket reply.',
        'Open the intranet to read it.',
    );

    expect($alert->via($user))->not->toContain(WebPushChannel::class)
        ->and($alert->via($user))->toContain('database');
});

it('sends an operations alert from Notifier', function () {
    Notification::fake();
    Mail::fake();

    $member = Helpdesk::member(RoleName::Staff, ['email' => 'ops@example.test']);
    enableCategoryPush($member, NotificationCategory::Operations);
    subscribeUser($member);

    Notifier::send([$member], 'Rota published: Site', ['Your rota is ready.'], '/operations/rota');

    Notification::assertSentTo($member, IntranetAlert::class, function (IntranetAlert $alert) use ($member) {
        expect($alert->category)->toBe(NotificationCategory::Operations)
            ->and($alert->title)->toBe('You have a new rota or operations notice.')
            ->and($alert->body)->toBe('Open the intranet to view it.')
            ->and($alert->via($member))->toContain(WebPushChannel::class);

        return true;
    });
});

it('sends an account alert for the access review reminder', function () {
    Notification::fake();

    $admin = User::factory()->create(['email' => 'admin-alert@example.test'])
        ->assignRole(RoleName::Admin->value);
    enableCategoryPush($admin, NotificationCategory::AccountSecurity);
    subscribeUser($admin);

    $this->artisan('access-review:remind')->assertSuccessful();

    Notification::assertSentTo($admin, IntranetAlert::class, function (IntranetAlert $alert) use ($admin) {
        expect($alert->category)->toBe(NotificationCategory::AccountSecurity)
            ->and($alert->title)->toBe('You have an account alert on the APES Intranet.')
            ->and($alert->via($admin))->toContain(WebPushChannel::class);

        return true;
    });
});

it('sends an account alert when an addon is down', function () {
    Notification::fake();

    $admin = User::factory()->create(['email' => 'sysadmin@example.test'])
        ->assignRole(RoleName::Admin->value);
    enableCategoryPush($admin, NotificationCategory::AccountSecurity);
    subscribeUser($admin);

    $redis = new FakeAddonCheck('redis');
    $mysql = new FakeAddonCheck('mysql');
    $redis->up = false;
    app()->instance(SystemStatus::class, new SystemStatus([$mysql, $redis]));

    for ($i = 0; $i < 3; $i++) {
        $this->artisan('system:check')->assertSuccessful();
        $this->travel(5)->minutes();
    }

    Notification::assertSentTo($admin, IntranetAlert::class, function (IntranetAlert $alert) use ($admin) {
        expect($alert->category)->toBe(NotificationCategory::AccountSecurity)
            ->and($alert->title)->toBe('You have an account alert on the APES Intranet.')
            ->and($alert->via($admin))->toContain(WebPushChannel::class);

        return true;
    });
});

it('sends an announcements alert for due event reminders', function () {
    Notification::fake();
    Mail::fake();

    $user = User::factory()->create(['email' => 'rsvp@example.test'])
        ->assignRole(RoleName::Staff->value);
    enableCategoryPush($user, NotificationCategory::Announcements);
    subscribeUser($user);

    $event = Event::query()->create([
        'author_id' => $user->id,
        'title' => 'Confidential board briefing',
        'starts_at' => now()->addHour(),
        'reminder_at' => now()->subMinute(),
    ]);
    EventRsvp::query()->create([
        'event_id' => $event->id,
        'user_id' => $user->id,
        'status' => 'going',
    ]);

    expect(app(EventController::class)->sendDueReminders())->toBe(1);

    Notification::assertSentTo($user, IntranetAlert::class, function (IntranetAlert $alert) {
        expect($alert->category)->toBe(NotificationCategory::Announcements)
            ->and($alert->title)->toBe('You have a new intranet announcement.')
            ->and($alert->title)->not->toContain('Confidential')
            ->and($alert->body)->not->toContain('board');

        return true;
    });
});
