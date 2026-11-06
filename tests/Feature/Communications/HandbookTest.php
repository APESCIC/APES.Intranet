<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\HandbookAcknowledgement;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\HandbookVersion;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
});

it('asks members to acknowledge again after a new version', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $member = User::factory()->create()->assignRole(RoleName::Staff->value);

    $chapter = HandbookChapter::query()->create([
        'slug' => 'conduct',
        'title' => 'Conduct',
        'published' => true,
        'sort_order' => 1,
    ]);
    $v1 = HandbookVersion::query()->create([
        'handbook_chapter_id' => $chapter->id,
        'version_number' => 1,
        'body' => '<p>v1</p>',
        'published_at' => now(),
        'published_by' => $admin->id,
    ]);

    $this->actingAs($member)->post(route('communications.handbook.acknowledge', $chapter))->assertRedirect();
    expect(HandbookAcknowledgement::query()->where('user_id', $member->id)->where('handbook_version_id', $v1->id)->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('communications.handbook.versions.store', $chapter), [
        'body' => '<p>v2</p>',
    ])->assertRedirect();

    $this->actingAs($member)->get(route('communications.handbook.show', $chapter))
        ->assertOk()
        ->assertSee('I have read and understand this chapter');
});

it('lets HR export acknowledgement CSV', function () {
    $hr = User::factory()->create()->assignRole(RoleName::Hr->value);
    $chapter = HandbookChapter::query()->create([
        'slug' => 'hours',
        'title' => 'Hours',
        'published' => true,
        'sort_order' => 1,
    ]);
    HandbookVersion::query()->create([
        'handbook_chapter_id' => $chapter->id,
        'version_number' => 1,
        'body' => '<p>hours</p>',
        'published_at' => now(),
    ]);

    $this->actingAs($hr)->get(route('communications.handbook.acknowledgements', $chapter))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8')
        ->assertSee('name,email,acknowledged', false);
});
