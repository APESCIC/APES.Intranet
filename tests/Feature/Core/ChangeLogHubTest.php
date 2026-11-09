<?php

use App\Core\Access\RoleName;
use App\Core\Changelog\Release;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function hubRelease(string $version, array $overrides = []): Release
{
    [$major, $minor, $patch] = array_map('intval', explode('.', ltrim($version, 'v')));

    return Release::query()->create(array_merge([
        'version' => $version,
        'major' => $major,
        'minor' => $minor,
        'patch' => $patch,
        'title' => "Release {$version}",
        'released_at' => '2026-11-09',
        'summary' => "Summary for {$version}.",
        'audiences' => ['staff', 'volunteers', 'students'],
        'changes_by_type' => ['added' => ["Added in {$version}."]],
        'internal_notes' => null,
        'is_published' => true,
    ], $overrides));
}

function hubUser(RoleName ...$roles): User
{
    $user = User::factory()->create();
    foreach ($roles as $role) {
        $user->assignRole($role->value);
    }

    return $user;
}

function stampVersion(?string $version): void
{
    $version === null ? @unlink(base_path('VERSION')) : file_put_contents(base_path('VERSION'), $version);
}

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

afterEach(fn () => stampVersion(null));

it('sends guests to sign in from every change log route', function (string $path) {
    hubRelease('v0.1.0');

    $this->get($path)->assertRedirect(route('login'));
})->with(['/change-log', '/change-log?type=added', '/change-log/v0.1.0']);

it('lists published releases for the member, newest first', function () {
    hubRelease('v0.3.9');
    hubRelease('v0.3.10');

    $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.index'))
        ->assertOk()
        ->assertSeeInOrder(['v0.3.10 · Release v0.3.10', 'v0.3.9 · Release v0.3.9']);
});

it('never shows a staff-only release to a student, by list, filter or direct link', function () {
    hubRelease('v0.1.0', ['title' => 'Staff only tools', 'audiences' => ['staff'], 'changes_by_type' => ['fixed' => ['Staff thing fixed.']]]);
    hubRelease('v0.2.0', ['title' => 'Everyone']);
    $student = hubUser(RoleName::Student);

    $this->actingAs($student)->get(route('change-log.index'))->assertOk()
        ->assertSee('Everyone')->assertDontSee('Staff only tools');
    $this->actingAs($student)->get(route('change-log.index', ['type' => 'fixed']))->assertOk()
        ->assertDontSee('Staff only tools')->assertDontSee('Staff thing fixed.');
    $this->actingAs($student)->get(route('change-log.show', 'v0.1.0'))->assertNotFound();

    $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.show', 'v0.1.0'))->assertOk()->assertSee('Staff only tools');
});

it('shows volunteers only releases tagged for volunteers', function () {
    hubRelease('v0.1.0', ['title' => 'For students', 'audiences' => ['students']]);
    hubRelease('v0.2.0', ['title' => 'For volunteers', 'audiences' => ['volunteers']]);

    $this->actingAs(hubUser(RoleName::Volunteer))->get(route('change-log.index'))
        ->assertSee('For volunteers')->assertDontSee('For students');
});

it('shows nothing to a member with no account type', function () {
    hubRelease('v0.1.0', ['title' => 'Hidden from no-type']);

    $this->actingAs(hubUser(RoleName::Supervisor))->get(route('change-log.index'))
        ->assertOk()->assertDontSee('Hidden from no-type')->assertSee('There are no release notes for you yet.');
});

it('never sends drafts or internal notes to non-admins', function () {
    hubRelease('v0.1.0', ['internal_notes' => ['Secret-ish admin context.']]);
    hubRelease('v0.2.0', ['title' => 'Unreleased draft', 'is_published' => false]);
    $staff = hubUser(RoleName::Staff, RoleName::Management, RoleName::Hr);

    foreach ([route('change-log.index'), route('change-log.show', 'v0.1.0')] as $url) {
        $this->actingAs($staff)->get($url)->assertOk()
            ->assertDontSee('Secret-ish admin context.')
            ->assertDontSee('Unreleased draft')
            ->assertDontSee('Internal notes');
    }
    $this->actingAs($staff)->get(route('change-log.show', 'v0.2.0'))->assertNotFound();
});

it('shows drafts and internal notes to admins', function () {
    hubRelease('v0.1.0', ['internal_notes' => ['Admin context.']]);
    hubRelease('v0.2.0', ['title' => 'Unreleased draft', 'is_published' => false]);
    $admin = hubUser(RoleName::Staff, RoleName::Admin);

    $this->actingAs($admin)->get(route('change-log.index'))
        ->assertSee('Unreleased draft')->assertSee('Draft, admins only');
    $this->actingAs($admin)->get(route('change-log.show', 'v0.1.0'))
        ->assertSee('Internal notes, admins only')->assertSee('Admin context.');
    $this->actingAs($admin)->get(route('change-log.show', 'v0.2.0'))->assertOk();
});

it('marks the deployed version as current, not the newest entry', function () {
    hubRelease('v0.3.10');
    hubRelease('v0.3.11');
    stampVersion('v0.3.10');

    $html = $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.index'))->assertOk()->getContent();
    $newest = (string) str($html)->after('release-v0-3-11')->before('</article>');
    $deployed = (string) str($html)->after('release-v0-3-10')->before('</article>');

    expect($deployed)->toContain('Current version')
        ->and($newest)->not->toContain('Current version');
});

it('filters releases by type of change', function () {
    hubRelease('v0.1.0', ['title' => 'Only fixes', 'changes_by_type' => ['fixed' => ['A fix.']]]);
    hubRelease('v0.2.0', ['title' => 'Only additions', 'changes_by_type' => ['added' => ['An addition.']]]);
    $staff = hubUser(RoleName::Staff);

    $this->actingAs($staff)->get(route('change-log.index', ['type' => 'fixed']))
        ->assertSee('Only fixes')->assertSee('A fix.')->assertDontSee('Only additions');
    $this->actingAs($staff)->get(route('change-log.index', ['type' => 'nonsense']))
        ->assertOk()->assertSee('Only fixes')->assertSee('Only additions');
});

it('has a keyboard and screen-reader friendly filter', function () {
    hubRelease('v0.1.0');

    $html = $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.index', ['type' => 'security']))
        ->assertSee('<nav class="changelog-filter mb-6" aria-label="Filter by type of change">', false)
        ->assertDontSee('<select', false)
        ->getContent();

    expect($html)->toMatch('/type=security"\s+aria-current="page"/')
        ->and(preg_match_all('/aria-current="page"/', (string) str($html)->after('changelog-filter')->before('</nav>')))->toBe(1);
});

it('says so when there is nothing to show', function () {
    $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.index'))
        ->assertOk()->assertSee('There are no release notes for you yet.');
});

it('returns 404 for a version that does not exist', function () {
    $this->actingAs(hubUser(RoleName::Staff))->get('/change-log/v9.9.9')->assertNotFound();
    $this->actingAs(hubUser(RoleName::Staff))->get('/change-log/latest')->assertNotFound();
});

it('lists the change log under Support in the menu', function () {
    $this->actingAs(hubUser(RoleName::Staff))->get(route('change-log.index'))
        ->assertSee('data-nav-key="change-log"', false);
});
