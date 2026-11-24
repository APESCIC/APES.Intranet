<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('grants legal.pages.manage to legal and admin only', function (RoleName $role, bool $canManage) {
    $user = User::factory()->create()->assignRole($role->value);

    expect($user->can(AccessMatrix::LEGAL_PAGES_MANAGE))->toBe($canManage);
})->with([
    'staff' => [RoleName::Staff, false],
    'legal' => [RoleName::Legal, true],
    'admin' => [RoleName::Admin, true],
    'hr' => [RoleName::Hr, false],
]);

it('treats legal as a core slug that cannot be disabled', function () {
    $modules = app(ModuleRegistry::class);

    expect($modules->isCoreSlug('legal'))->toBeTrue();
    expect(fn () => $modules->disable('legal', 'test', null))
        ->toThrow(InvalidArgumentException::class, 'Core cannot be disabled.');
});

it('shows Legal pages under Apps and content for admins', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);

    $this->actingAs($admin)
        ->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('data-nav-key="legal-pages"', false)
        ->assertSee('data-nav-key="legal-footer-links"', false)
        ->assertSee('data-nav-key="legal-acceptance"', false)
        ->assertSee('Legal pages');
});
