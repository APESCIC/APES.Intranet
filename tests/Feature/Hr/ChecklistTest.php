<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\HrPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Ticket;
use Modules\Hr\Models\ChecklistTemplate;
use Modules\Hr\Models\MemberChecklist;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HrPermissionsSeeder::class);
    Helpdesk::seed();
});

/**
 * @param  list<RoleName>  $roles
 */
function checklistActor(object $test, array $roles): User
{
    $user = User::factory()->create()->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('starts the right onboarding checklist for a staff member', function () {
    checklistActor($this, [RoleName::Staff, RoleName::Hr]);
    $member = User::factory()->create(['name' => 'New Starter'])->assignRole(RoleName::Staff->value);

    $this->post(route('hr.checklists.store'), [
        'user_id' => $member->id,
        'kind' => ChecklistTemplate::KIND_ONBOARDING,
        'member_type' => 'staff',
    ])->assertRedirect();

    $checklist = MemberChecklist::query()->where('user_id', $member->id)->firstOrFail();
    expect($checklist->kind)->toBe('onboarding')
        ->and($checklist->member_type)->toBe('staff')
        ->and($checklist->items)->toHaveCount(3)
        ->and($checklist->it_ticket_id)->toBeNull()
        ->and($checklist->items->pluck('owner_role')->all())->toContain('hr', 'manager', 'it');
});

it('raises an IT ticket when offboarding starts', function () {
    $hr = checklistActor($this, [RoleName::Staff, RoleName::Hr]);
    $member = User::factory()->create(['name' => 'Leaving Person', 'email' => 'leaving@example.test'])->assignRole(RoleName::Staff->value);

    $this->post(route('hr.checklists.store'), [
        'user_id' => $member->id,
        'kind' => ChecklistTemplate::KIND_OFFBOARDING,
        'member_type' => 'volunteer',
    ])->assertRedirect();

    $checklist = MemberChecklist::query()->where('user_id', $member->id)->firstOrFail();
    expect($checklist->it_ticket_id)->not->toBeNull()
        ->and($checklist->member_type)->toBe('volunteer');

    $ticket = Ticket::query()->findOrFail($checklist->it_ticket_id);
    expect($ticket->queue)->toBe(Ticket::QUEUE_IT)
        ->and($ticket->subject)->toContain('Leaving Person')
        ->and($ticket->requester_id)->toBe($hr->id);
});

it('refuses staff without HR access', function () {
    checklistActor($this, [RoleName::Staff]);
    $this->get(route('hr.checklists.index'))->assertForbidden();
});
