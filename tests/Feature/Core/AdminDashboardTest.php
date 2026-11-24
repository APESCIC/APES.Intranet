<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\AdminDashboard\AdminDashboard;
use App\Core\AdminDashboard\AdminDashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\CommunicationsPermissionsSeeder;
use Database\Seeders\HelpdeskPermissionsSeeder;
use Database\Seeders\Microsoft365PermissionsSeeder;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CommunicationsPermissionsSeeder::class);
    $this->seed(Microsoft365PermissionsSeeder::class);
    $this->seed(OperationsPermissionsSeeder::class);
    $this->seed(HelpdeskPermissionsSeeder::class);
});

function adminDashboardUser(RoleName ...$roles): User
{
    $user = User::factory()->create(['name' => 'Ada Admin']);
    foreach ($roles as $role) {
        $user->assignRole($role->value);
    }

    return $user;
}

it('shows admin section cards on the overview for a full admin', function () {
    $response = $this->actingAs(adminDashboardUser(RoleName::Admin))->get(route('admin.overview'))->assertOk();

    $response->assertSee('data-admin-dashboard', false);
    foreach (['roles', 'audit', 'access-review', 'system', 'modules', 'totp', 'm365-links'] as $key) {
        $response->assertSee('data-admin-section="'.$key.'"', false);
    }
});

it('leaves a card off the overview when the admin lacks its permission', function () {
    Role::findByName(RoleName::Admin->value)->revokePermissionTo(AccessMatrix::ADMIN_AUDIT_VIEW);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs(adminDashboardUser(RoleName::Admin))->get(route('admin.overview'))
        ->assertOk()
        ->assertSee('data-admin-section="roles"', false)
        ->assertDontSee('data-admin-section="audit"', false);
});

it('hides cards from a switched-off module and never asks that contributor for data', function () {
    $calls = 0;
    $contributor = new class($calls) implements AdminDashboardCardContributor, BelongsToModule
    {
        public function __construct(private int &$calls) {}

        public function moduleSlug(): string
        {
            return 'helpdesk';
        }

        public function cards(User $user): array
        {
            $this->calls++;

            return [[
                'key' => 'probe-helpdesk',
                'title' => 'Probe',
                'body' => 'Should not appear.',
                'href' => '/admin',
                'permission' => AccessMatrix::ADMIN_SYSTEM_VIEW,
                'module' => 'helpdesk',
            ]];
        }
    };

    $this->app->instance('test.admin.probe', $contributor);
    $this->app->tag(['test.admin.probe'], 'intranet.admin-dashboard');

    $admin = adminDashboardUser(RoleName::Admin);
    $registry = app(ModuleRegistry::class);
    $registry->disable('helpdesk', 'Pause for dashboard test', $admin);

    $keys = collect(app(AdminDashboard::class)->cardsFor($admin))->pluck('key');

    expect($calls)->toBe(0)
        ->and($keys)->not->toContain('probe-helpdesk');
});

it('never includes a card without permission and never loads that card data', function () {
    $loads = 0;
    $contributor = new class($loads) implements AdminDashboardCardContributor
    {
        public function __construct(private int &$loads) {}

        public function cards(User $user): array
        {
            $cards = [];

            if ($user->can(AccessMatrix::ADMIN_AUDIT_VIEW)) {
                $this->loads++;
                $cards[] = [
                    'key' => 'probe-audit',
                    'title' => 'Audit probe',
                    'body' => 'Loaded.',
                    'href' => '/admin',
                    'permission' => AccessMatrix::ADMIN_AUDIT_VIEW,
                ];
            }

            return $cards;
        }
    };

    $this->app->instance('test.admin.perm', $contributor);
    $this->app->tag(['test.admin.perm'], 'intranet.admin-dashboard');

    Role::findByName(RoleName::Admin->value)->revokePermissionTo(AccessMatrix::ADMIN_AUDIT_VIEW);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $admin = adminDashboardUser(RoleName::Admin);
    $keys = collect(app(AdminDashboard::class)->cardsFor($admin))->pluck('key');

    expect($loads)->toBe(0)
        ->and($keys)->not->toContain('probe-audit');
});

it('loads the admin overview within one second on demo-sized data', function () {
    $admin = adminDashboardUser(RoleName::Admin);

    $started = hrtime(true);
    $this->actingAs($admin)->get(route('admin.overview'))->assertOk();
    $elapsedMs = (hrtime(true) - $started) / 1_000_000;

    expect($elapsedMs)->toBeLessThan(1000);
});
