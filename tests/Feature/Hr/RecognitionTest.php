<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\HrPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\UpdatePost;
use Modules\Hr\Models\Recognition;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HrPermissionsSeeder::class);
});

/**
 * @param  list<RoleName>  $roles
 */
function recognitionActor(object $test, array $roles, array $attributes = []): User
{
    $user = User::factory()->create($attributes)->syncRoles(array_map(fn (RoleName $role) => $role->value, $roles));
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('lets a member give recognition that shows on the recipient profile', function () {
    $giver = recognitionActor($this, [RoleName::Staff], ['name' => 'Giver Example']);
    $recipient = User::factory()->create(['name' => 'Recipient Example'])->assignRole(RoleName::Staff->value);

    $this->get(route('hr.recognition.create'))
        ->assertOk()
        ->assertSee('Give recognition')
        ->assertSee('Also post this to the updates feed');

    $this->post(route('hr.recognition.store'), [
        'recipient_id' => $recipient->id,
        'message' => 'Thank you for covering the late shift.',
    ])->assertRedirect(route('people.profile.show', $recipient));

    $recognition = Recognition::query()->first();
    expect($recognition)->not->toBeNull()
        ->and($recognition->giver_id)->toBe($giver->id)
        ->and($recognition->recipient_id)->toBe($recipient->id)
        ->and($recognition->posted_to_feed)->toBeFalse()
        ->and(UpdatePost::query()->count())->toBe(0);

    $this->get(route('people.profile.show', $recipient))
        ->assertOk()
        ->assertSee('Recognition')
        ->assertSee('Thank you for covering the late shift.')
        ->assertSee('Giver Example');
});

it('posts to the updates feed only when the giver opts in', function () {
    $giver = recognitionActor($this, [RoleName::Staff], ['name' => 'Feed Giver']);
    $recipient = User::factory()->create(['name' => 'Feed Recipient'])->assignRole(RoleName::Staff->value);

    $this->post(route('hr.recognition.store'), [
        'recipient_id' => $recipient->id,
        'message' => 'Brilliant facilitation today.',
        'post_to_feed' => '1',
    ])->assertRedirect(route('people.profile.show', $recipient));

    $recognition = Recognition::query()->firstOrFail();
    $post = UpdatePost::query()->firstOrFail();

    expect($recognition->posted_to_feed)->toBeTrue()
        ->and($recognition->update_post_id)->toBe($post->id)
        ->and($post->title)->toContain('Feed Recipient')
        ->and($post->body)->toContain('Brilliant facilitation today.')
        ->and($post->author_id)->toBe($giver->id);
});

it('refuses self-recognition', function () {
    $member = recognitionActor($this, [RoleName::Staff]);

    $this->from(route('hr.recognition.create'))
        ->post(route('hr.recognition.store'), [
            'recipient_id' => $member->id,
            'message' => 'I am great.',
        ])
        ->assertRedirect(route('hr.recognition.create'))
        ->assertSessionHasErrors('recipient_id');

    expect(Recognition::query()->count())->toBe(0);
});
