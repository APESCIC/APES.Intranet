<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\CommunicationsBootstrapSeeder;
use Database\Seeders\HelpdeskBootstrapSeeder;
use Database\Seeders\Microsoft365BootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Microsoft365\Models\MicrosoftLink;
use Modules\Microsoft365\Support\MicrosoftHelp;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsBootstrapSeeder::class);
    $this->seed(Microsoft365BootstrapSeeder::class);
    $this->seed(HelpdeskBootstrapSeeder::class);
});

it('shows the help note to every member in the Microsoft 365 menu section and on the tile', function (RoleName $role) {
    $member = User::factory()->create()->assignRole($role->value);

    $page = $this->actingAs($member)->get(route('dashboard'))->assertOk();

    $page->assertSee('data-nav-note="microsoft-365"', false)
        ->assertSee('data-card-note', false)
        ->assertSee('Most staff have a Microsoft 365 licence; volunteers and students may have a lighter set of apps.');

    // Top section dropdown, phone menu panel, and the dashboard tile.
    expect(substr_count($page->getContent(), 'no licence'))->toBe(3);
})->with([RoleName::Staff, RoleName::Volunteer, RoleName::Student]);

it('links the note to the handbook page and to a Microsoft 365 help desk ticket', function () {
    $member = User::factory()->create()->assignRole(RoleName::Volunteer->value);
    $handbook = route('communications.handbook.show', 'microsoft-365-accounts');
    $helpdesk = route('helpdesk.tickets.create', ['category' => 'microsoft-365']);

    $page = $this->actingAs($member)->get(route('dashboard'))->assertOk()->getContent();

    expect(substr_count($page, 'href="'.$handbook.'"'))->toBe(3)
        ->and(substr_count($page, 'href="'.e($helpdesk).'"'))->toBe(3);

    $this->actingAs($member)->get($handbook)->assertOk()->assertSee('Microsoft 365 accounts and licences');
    $this->actingAs($member)->get($helpdesk)->assertOk()->assertSee('Microsoft 365 apps and licences');
});

it('keeps every app link on the menu and the tile; the note does not hide any', function () {
    $volunteer = User::factory()->create()->assignRole(RoleName::Volunteer->value);
    $forAll = MicrosoftLink::query()->where('active', true)->where('audience', 'all')->pluck('name');

    $page = $this->actingAs($volunteer)->get(route('dashboard'))->assertOk();
    foreach ($forAll as $name) {
        $page->assertSee($name);
    }
});

it('words the note from the handbook licence guidance', function () {
    expect(MicrosoftHelp::note())->toContain('Most staff have a Microsoft 365 licence')
        ->and(array_keys(MicrosoftHelp::links()))->toBe(['Read the handbook', 'Ask IT']);
});
