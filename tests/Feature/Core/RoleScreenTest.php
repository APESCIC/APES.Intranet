<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('sends guests away from the roles screen', function () {
    $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
});

it('shows the roles screen to admins only', function (RoleName $role, int $status) {
    $user = User::factory()->create()->assignRole($role->value);

    $this->actingAs($user)->get(route('admin.roles.index'))->assertStatus($status);
})->with([
    'staff' => [RoleName::Staff, 403],
    'volunteer' => [RoleName::Volunteer, 403],
    'student' => [RoleName::Student, 403],
    'supervisor' => [RoleName::Supervisor, 403],
    'management' => [RoleName::Management, 403],
    'hr' => [RoleName::Hr, 403],
    'legal' => [RoleName::Legal, 403],
    'admin' => [RoleName::Admin, 200],
]);

it('lists every role and offers no way to change them', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $response = $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk();

    foreach (RoleName::cases() as $role) {
        $response->assertSee($role->label());
    }
    $main = str($response->getContent())->after('<main')->before('</main>');
    expect((string) $main)->not->toContain('<form');
});

it('shows each approved group next to its role, permissions and member count', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    User::factory()->count(2)->create()->each->assignRole(RoleName::Volunteer->value);

    $html = $this->actingAs($admin)->get(route('admin.roles.index'))->assertOk()->getContent();

    foreach (config('intranet.group_roles') as $group => $roleName) {
        $row = (string) str($html)->after('data-role="'.$roleName.'"')->before('</tr>');
        expect($row)->toContain($group)
            ->and($row)->toContain(RoleName::from($roleName)->label());

        foreach (Role::findByName($roleName)->permissions as $permission) {
            expect($row)->toContain($permission->name);
        }
    }

    $volunteers = (string) str($html)->after('data-role="volunteer"')->before('</tr>');
    expect($volunteers)->toMatch('/<td class="p-4">2<\/td>/');
});

it('shows counts but no names or emails of members', function () {
    $admin = User::factory()->create(['name' => 'Ada Admin'])->assignRole(RoleName::Admin->value);
    $volunteer = User::factory()->create(['name' => 'Vera Volunteer', 'email' => 'vera@example.test'])->assignRole(RoleName::Volunteer->value);

    $main = (string) str($this->actingAs($admin)->get(route('admin.roles.index'))->getContent())->after('<main')->before('</main>');

    expect($main)->not->toContain($volunteer->name)
        ->and($main)->not->toContain($volunteer->email);
});

it('treats supervisors, management and admins as supervisors', function (RoleName $role, bool $supervises) {
    $user = User::factory()->create()->assignRole($role->value);

    expect($user->isSupervisor())->toBe($supervises);
})->with([
    'staff' => [RoleName::Staff, false],
    'volunteer' => [RoleName::Volunteer, false],
    'student' => [RoleName::Student, false],
    'supervisor' => [RoleName::Supervisor, true],
    'management' => [RoleName::Management, true],
    'hr' => [RoleName::Hr, false],
    'legal' => [RoleName::Legal, false],
    'admin' => [RoleName::Admin, true],
]);

it('renames existing manager role assignments to management', function () {
    $migration = require database_path('migrations/2026_11_05_200000_rename_manager_role_to_management.php');
    $migration->down();
    $user = User::factory()->create()->assignRole('manager');

    $migration->up();

    expect(Role::where('name', 'manager')->exists())->toBeFalse()
        ->and($user->fresh()->getRoleNames()->all())->toBe(['management'])
        ->and($user->fresh()->can('team.manage'))->toBeTrue();
});
