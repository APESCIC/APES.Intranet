<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Communications\Http\Controllers\EventController;
use Modules\Communications\Mail\EventReminderMail;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\EventRsvp;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

it('lets a member change their RSVP', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);
    $event = Event::query()->create([
        'author_id' => $user->id,
        'title' => 'Town hall',
        'starts_at' => now()->addDay(),
    ]);

    $this->actingAs($user)->post(route('communications.events.rsvp', $event), ['status' => 'going'])->assertRedirect();
    $this->actingAs($user)->post(route('communications.events.rsvp', $event), ['status' => 'declined'])->assertRedirect();

    expect(EventRsvp::query()->where('event_id', $event->id)->where('user_id', $user->id)->value('status'))->toBe('declined');
});

it('sends reminder email for due events', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'member@example.test'])->assignRole(RoleName::Staff->value);
    $event = Event::query()->create([
        'author_id' => $user->id,
        'title' => 'Reminder event',
        'starts_at' => now()->addHour(),
        'reminder_at' => now()->subMinute(),
    ]);
    EventRsvp::query()->create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);

    expect(app(EventController::class)->sendDueReminders())->toBe(1);
    Mail::assertSent(EventReminderMail::class, fn (EventReminderMail $mail) => $mail->hasTo('member@example.test'));
    $event->refresh();
    expect($event->reminder_sent_at)->not->toBeNull();
});

it('exports an iCal file', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);
    $event = Event::query()->create([
        'author_id' => $user->id,
        'title' => 'Outlook import',
        'starts_at' => now()->addDay(),
        'location' => 'APES office',
    ]);

    $this->actingAs($user)->get(route('communications.events.ical', $event))
        ->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VCALENDAR', false)
        ->assertSee('SUMMARY:Outlook import', false);
});
