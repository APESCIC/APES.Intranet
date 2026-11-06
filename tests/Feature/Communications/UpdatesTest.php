<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\UpdatePost;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

it('shows a post only to its audience', function () {
    $author = User::factory()->create()->assignRole(RoleName::Admin->value);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);
    $volunteer = User::factory()->create()->assignRole(RoleName::Volunteer->value);

    $post = UpdatePost::query()->create([
        'author_id' => $author->id,
        'title' => 'Staff only news',
        'body' => '<p>Hello</p>',
        'audiences' => ['staff'],
        'published_at' => now(),
        'comments_enabled' => true,
    ]);

    $this->actingAs($staff)->get(route('communications.updates.show', $post))->assertOk();
    $this->actingAs($volunteer)->get(route('communications.updates.show', $post))->assertForbidden();
});

it('records a read receipt for the author to count', function () {
    $author = User::factory()->create()->assignRole([RoleName::Admin->value]);
    $reader = User::factory()->create()->assignRole(RoleName::Staff->value);

    $post = UpdatePost::query()->create([
        'author_id' => $author->id,
        'title' => 'Hello',
        'body' => '<p>Body</p>',
        'audiences' => [],
        'published_at' => now(),
    ]);

    $this->actingAs($reader)->get(route('communications.updates.show', $post))
        ->assertOk()
        ->assertSee('Read by 1 person');
});

it('strips scripts when publishing an update', function () {
    $author = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($author)->post(route('communications.updates.store'), [
        'title' => 'Safe',
        'body' => '<p onclick="x">Hi<script>bad()</script></p>',
        'comments_enabled' => '1',
    ])->assertRedirect();

    $post = UpdatePost::query()->first();
    expect($post->body)->not->toContain('<script')
        ->and($post->body)->not->toContain('onclick');
});
