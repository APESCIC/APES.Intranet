<?php

use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboardLayout;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\HelpdeskPermissionsSeeder;
use Database\Seeders\Microsoft365PermissionsSeeder;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
    $this->seed(Microsoft365PermissionsSeeder::class);
    $this->seed(OperationsPermissionsSeeder::class);
    $this->seed(HelpdeskPermissionsSeeder::class);
});

function sharedDefaultAdmin(string $email = 'admin@example.test'): User
{
    $user = User::factory()->create(['email' => $email]);
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

it('lets only the Director set the shared default', function () {
    config(['intranet.director_email' => 'director@apes.test']);

    $director = sharedDefaultAdmin('director@apes.test');
    $other = sharedDefaultAdmin('other-admin@apes.test');

    $html = $this->actingAs($director)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $html, $matches);
    $keys = $matches[1];
    expect($keys)->not->toBeEmpty();

    $this->actingAs($director)->post(route('admin.overview.layout.save'), [
        'ordered_keys' => array_reverse($keys),
        'hidden_keys' => [],
    ])->assertRedirect();

    $this->actingAs($other)->post(route('admin.overview.layout.shared-default'), ['confirm' => 1])
        ->assertForbidden();

    $this->actingAs($director)->post(route('admin.overview.layout.shared-default'), ['confirm' => 1])
        ->assertRedirect(route('admin.overview'));

    expect(AdminDashboardLayout::query()->whereNull('user_id')->exists())->toBeTrue();
});

it('gives new admins the shared default until they customise', function () {
    config(['intranet.director_email' => 'director@apes.test']);
    $director = sharedDefaultAdmin('director@apes.test');

    $html = $this->actingAs($director)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $html, $matches);
    $keys = $matches[1];
    $reversed = array_reverse($keys);

    $this->actingAs($director)->post(route('admin.overview.layout.save'), [
        'ordered_keys' => $reversed,
        'hidden_keys' => [],
    ]);
    $this->actingAs($director)->post(route('admin.overview.layout.shared-default'), ['confirm' => 1]);
    $this->actingAs($director)->post(route('admin.overview.layout.reset'));

    $newAdmin = sharedDefaultAdmin('new-admin@apes.test');
    $view = $this->actingAs($newAdmin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $view, $after);
    expect($after[1][0])->toBe($reversed[0]);

    $custom = array_reverse($after[1]);
    $this->actingAs($newAdmin)->post(route('admin.overview.layout.save'), [
        'ordered_keys' => $custom,
        'hidden_keys' => [],
    ]);

    $kept = $this->actingAs($newAdmin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $kept, $personal);
    expect($personal[1][0])->toBe($custom[0]);

    $this->actingAs($newAdmin)->post(route('admin.overview.layout.reset'))->assertRedirect();
    $reset = $this->actingAs($newAdmin)->get(route('admin.overview'))->assertOk()->getContent();
    preg_match_all('/data-admin-card="([^"]+)"/', $reset, $back);
    expect($back[1][0])->toBe($reversed[0]);
});

it('shows Set as shared default only to the Director in edit mode', function () {
    config(['intranet.director_email' => 'director@apes.test']);

    $director = sharedDefaultAdmin('director@apes.test');
    $other = sharedDefaultAdmin('other-admin@apes.test');

    $this->actingAs($director)->get(route('admin.overview', ['edit' => 1]))
        ->assertOk()
        ->assertSee('data-set-shared-default', false);

    $this->actingAs($other)->get(route('admin.overview', ['edit' => 1]))
        ->assertOk()
        ->assertDontSee('data-set-shared-default', false);
});
