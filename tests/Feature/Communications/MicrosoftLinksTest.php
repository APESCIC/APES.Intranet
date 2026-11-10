<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Models\User;
use Database\Seeders\CommunicationsBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Communications\Models\MicrosoftLink;
use Modules\Communications\Support\MicrosoftUrlAllowlist;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsBootstrapSeeder::class);
});

it('hides staff-only Microsoft links from volunteers and students', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);
    $volunteer = User::factory()->create()->assignRole(RoleName::Volunteer->value);

    $staffKeys = collect(Menu::for($staff, MenuMode::Staff))->flatMap(fn (NavGroup $g) => array_map(fn (NavItem $i) => $i->key, $g->items))->all();
    $volKeys = collect(Menu::for($volunteer, MenuMode::Staff))->flatMap(fn (NavGroup $g) => array_map(fn (NavItem $i) => $i->key, $g->items))->all();

    expect($staffKeys)->toContain('m365-bookings', 'm365-loop', 'm365-planner')
        ->and($volKeys)->toContain('m365-planner')
        ->and($volKeys)->not->toContain('m365-bookings', 'm365-loop');
});

it('rejects non-allowlisted Microsoft URLs', function () {
    expect(MicrosoftUrlAllowlist::allows('https://evil.example/x', config('communications.m365_url_allowlist')))->toBeFalse()
        ->and(MicrosoftUrlAllowlist::allows('http://outlook.office.com/mail/', config('communications.m365_url_allowlist')))->toBeFalse()
        ->and(MicrosoftUrlAllowlist::allows('https://outlook.office.com/mail/', config('communications.m365_url_allowlist')))->toBeTrue();
});

it('writes Microsoft link changes to the audit log', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)->post(route('admin.m365-links.store'), [
        'key' => 'forms',
        'name' => 'Forms',
        'url' => 'https://forms.office.com/',
        'icon' => 'clipboard-list',
        'audience' => 'all',
        'sort_order' => 90,
        'active' => '1',
    ])->assertRedirect();

    expect(AuditLog::query()->where('event', 'comms.m365_link_created')->exists())->toBeTrue();
});

it('seeds Planner for all members', function () {
    $planner = MicrosoftLink::query()->where('key', 'planner')->first();
    expect($planner)->not->toBeNull()
        ->and($planner->audience)->toBe('all')
        ->and($planner->url)->toBe('https://planner.cloud.microsoft/');
});

it('shows the same Microsoft apps on the dashboard tile as the menu', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $response = $this->actingAs($staff)->get(route('dashboard'))->assertOk();
    $response->assertSee('Microsoft 365 apps')
        ->assertSee('Bookings')
        ->assertSee('(opens in a new tab)');
});

it('hides the apps tile when there are no active links', function () {
    MicrosoftLink::query()->update(['active' => false]);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Microsoft 365 apps');
});
