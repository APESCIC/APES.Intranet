<?php

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);

    $this->modulesPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'apes-modules-admin-'.uniqid('', true);
    File::ensureDirectoryExists($this->modulesPath.'/alpha');
    File::put($this->modulesPath.'/alpha/module.php', <<<'PHP'
<?php

return [
    'slug' => 'alpha',
    'name' => 'Alpha',
    'kind' => 'module',
    'version' => '0.1.0',
    'description' => 'Parent module for depends_on tests.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => [],
    'settings' => [
        'label' => [
            'type' => 'string',
            'default' => 'Alpha',
            'label' => 'Label',
        ],
        'api_token' => [
            'type' => 'secret_ref',
            'default' => ['env' => null, 'vault' => null],
            'label' => 'API token',
        ],
    ],
];
PHP);

    File::ensureDirectoryExists($this->modulesPath.'/beta');
    File::put($this->modulesPath.'/beta/module.php', <<<'PHP'
<?php

return [
    'slug' => 'beta',
    'name' => 'Beta',
    'kind' => 'module',
    'version' => '0.1.0',
    'description' => 'Depends on Alpha.',
    'default_enabled' => true,
    'depends_on' => ['alpha'],
    'cloudron_addons' => [],
    'settings' => [],
];
PHP);

    $this->app->singleton(ModuleRegistry::class, fn ($app) => new ModuleRegistry(
        $app->make(AuditLogger::class),
        $app->make('cache.store'),
        $this->modulesPath,
    ));
});

afterEach(function () {
    if (isset($this->modulesPath) && File::isDirectory($this->modulesPath)) {
        File::deleteDirectory($this->modulesPath);
    }
});

function adminModulesUser(): User
{
    $user = User::factory()->create(['name' => 'Modules Admin']);
    $user->assignRole(RoleName::Admin->value);

    return $user;
}

/**
 * @return array<string, mixed>
 */
function modulesFreshSignIn(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

it('requires fresh sign-in before the Modules screen', function () {
    fakeCloudron($this);
    config(['intranet.fresh_signin_minutes' => 15]);

    signInWithCloudron($this, ['intranet.staff', 'intranet.admin']);
    $this->travel(16)->minutes();

    $this->get(route('admin.modules.index'))->assertRedirect(route('auth.reauth'));
});

it('lists modules for an admin with a fresh sign-in', function () {
    $response = $this->actingAs(adminModulesUser())
        ->withSession(modulesFreshSignIn())
        ->get(route('admin.modules.index'));

    $response->assertOk()
        ->assertSee('data-module="core"', false)
        ->assertSee('data-module="alpha"', false)
        ->assertSee('data-module="beta"', false)
        ->assertSee('Core')
        ->assertSee('Alpha')
        ->assertSee('Beta')
        ->assertSee('data-version="0.1.0"', false)
        ->assertSee('Always on');
});

it('refuses the Modules screen without the manage permission', function () {
    Role::findByName(RoleName::Admin->value)->revokePermissionTo(AccessMatrix::ADMIN_MODULES_MANAGE);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs(adminModulesUser())
        ->withSession(modulesFreshSignIn())
        ->get(route('admin.modules.index'))
        ->assertForbidden();
});

it('blocks disable while a dependent module is still enabled and audits a successful change', function () {
    $admin = adminModulesUser();

    $this->actingAs($admin)
        ->withSession(modulesFreshSignIn())
        ->post(route('admin.modules.state', 'alpha'), [
            'enabled' => false,
            'reason' => 'Trying to disable parent',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(app(ModuleRegistry::class)->enabled('alpha'))->toBeTrue()
        ->and(AuditLog::query()->where('event', 'admin.module.disabled')->count())->toBe(0);

    $this->actingAs($admin)
        ->withSession(modulesFreshSignIn())
        ->post(route('admin.modules.state', 'beta'), [
            'enabled' => false,
            'reason' => 'Pause beta first',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(app(ModuleRegistry::class)->enabled('beta'))->toBeFalse();
    expect(AuditLog::query()->where('event', 'admin.module.disabled')->count())->toBe(1);

    $this->actingAs($admin)
        ->withSession(modulesFreshSignIn())
        ->post(route('admin.modules.state', 'alpha'), [
            'enabled' => false,
            'reason' => 'Pause alpha after beta',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(app(ModuleRegistry::class)->enabled('alpha'))->toBeFalse()
        ->and(AuditLog::query()->where('event', 'admin.module.disabled')->count())->toBe(2);
});

it('saves typed settings and shows secret_ref as set without storing a secret value', function () {
    $admin = adminModulesUser();

    $this->actingAs($admin)
        ->withSession(modulesFreshSignIn())
        ->post(route('admin.modules.settings', 'alpha'), [
            'settings' => [
                'label' => 'Renamed Alpha',
                'api_token' => [
                    'env' => 'ALPHA_API_TOKEN',
                    'vault' => 'Alpha API Token',
                ],
            ],
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $registry = app(ModuleRegistry::class);
    expect($registry->setting('alpha', 'label'))->toBe('Renamed Alpha')
        ->and($registry->secretRefIsSet('alpha', 'api_token'))->toBeTrue()
        ->and(json_encode($registry->setting('alpha', 'api_token')))->not->toContain('sk-');

    $this->actingAs($admin)
        ->withSession(modulesFreshSignIn())
        ->get(route('admin.modules.index'))
        ->assertOk()
        ->assertSee('data-secret-ref="set"', false)
        ->assertDontSee('sk-');
});
