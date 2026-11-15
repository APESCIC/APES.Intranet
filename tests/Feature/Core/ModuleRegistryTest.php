<?php

use App\Core\Audit\AuditLog;
use App\Core\Audit\AuditLogger;
use App\Core\Modules\InvalidModuleSettingException;
use App\Core\Modules\Models\ModuleSetting;
use App\Core\Modules\Models\ModuleState;
use App\Core\Modules\ModuleKind;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);

    $this->modulesPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'apes-modules-'.uniqid('', true);
    File::ensureDirectoryExists($this->modulesPath.'/sample');
    File::put($this->modulesPath.'/sample/module.php', <<<'PHP'
<?php

return [
    'slug' => 'sample',
    'name' => 'Sample',
    'kind' => 'module',
    'description' => 'Test module for the registry.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => ['mysql'],
    'settings' => [
        'title' => [
            'type' => 'string',
            'default' => 'Hello',
            'label' => 'Title',
        ],
        'limit' => [
            'type' => 'int',
            'default' => 10,
            'label' => 'Limit',
        ],
        'enabled_flag' => [
            'type' => 'bool',
            'default' => false,
            'label' => 'Enabled flag',
        ],
        'mode' => [
            'type' => 'enum',
            'default' => 'safe',
            'label' => 'Mode',
            'options' => ['safe', 'strict'],
        ],
        'home_url' => [
            'type' => 'url',
            'default' => 'https://example.test',
            'label' => 'Home URL',
        ],
        'api_token' => [
            'type' => 'secret_ref',
            'default' => ['env' => null, 'vault' => null],
            'label' => 'API token',
        ],
    ],
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

it('discovers manifests and uses the default enabled state when no row exists', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->manifest('sample'))->not->toBeNull()
        ->and($registry->manifest('sample')?->kind)->toBe(ModuleKind::Module)
        ->and($registry->enabled('sample'))->toBeTrue()
        ->and($registry->manifests())->toHaveCount(1);
});

it('audits enable and disable with the reason only and keeps the state row', function () {
    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $registry = app(ModuleRegistry::class);

    $registry->disable('sample', 'Supplier outage', $admin);

    expect($registry->enabled('sample'))->toBeFalse();

    $disabled = AuditLog::query()->where('event', 'admin.module.disabled')->firstOrFail();
    expect($disabled->actor_id)->toBe($admin->id)
        ->and($disabled->context)->toBe(['reason' => 'Supplier outage'])
        ->and($disabled->subject_type)->toBe((new ModuleState)->getMorphClass())
        ->and(ModuleState::query()->where('slug', 'sample')->value('enabled'))->toBeFalse();

    $registry->enable('sample', 'Supplier restored', $admin);

    expect($registry->enabled('sample'))->toBeTrue();
    $enabled = AuditLog::query()->where('event', 'admin.module.enabled')->firstOrFail();
    expect($enabled->context)->toBe(['reason' => 'Supplier restored']);
});

it('invalidates the enabled-state cache when the module is switched', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    expect($registry->enabled('sample'))->toBeTrue();
    expect(Cache::has(ModuleRegistry::CACHE_STATES))->toBeTrue();

    $registry->disable('sample', 'Pause for pilot', $admin);

    expect(Cache::has(ModuleRegistry::CACHE_STATES))->toBeFalse();
    expect($registry->enabled('sample'))->toBeFalse();
});

it('rejects invalid typed settings', function () {
    $registry = app(ModuleRegistry::class);

    expect(fn () => $registry->setSetting('sample', 'limit', 'nope'))
        ->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'mode', 'danger'))
        ->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'home_url', 'not-a-url'))
        ->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'enabled_flag', 'yes'))
        ->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'unknown', 'x'))
        ->toThrow(InvalidModuleSettingException::class);
});

it('stores typed settings and never stores secret values for secret_ref', function () {
    $registry = app(ModuleRegistry::class);

    $registry->setSettings('sample', [
        'title' => 'Welcome',
        'limit' => 25,
        'enabled_flag' => true,
        'mode' => 'strict',
        'home_url' => 'https://intranet.example.test/home',
        'api_token' => [
            'env' => 'SAMPLE_API_TOKEN',
            'vault' => 'Sample API Token',
        ],
    ]);

    expect($registry->setting('sample', 'title'))->toBe('Welcome')
        ->and($registry->setting('sample', 'limit'))->toBe(25)
        ->and($registry->setting('sample', 'enabled_flag'))->toBeTrue()
        ->and($registry->setting('sample', 'mode'))->toBe('strict')
        ->and($registry->secretRefIsSet('sample', 'api_token'))->toBeTrue();

    $stored = ModuleSetting::query()->where('slug', 'sample')->where('key', 'api_token')->value('value');
    expect($stored)->toBe([
        'env' => 'SAMPLE_API_TOKEN',
        'vault' => 'Sample API Token',
    ])
        ->and($stored)->not->toHaveKey('value')
        ->and(json_encode($stored))->not->toContain('sk-');

    expect(fn () => $registry->setSetting('sample', 'api_token', 'sk-live-secret-value'))
        ->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'api_token', [
        'env' => 'not-an-env-key',
        'vault' => 'Item',
    ]))->toThrow(InvalidModuleSettingException::class);

    expect(fn () => $registry->setSetting('sample', 'api_token', [
        'env' => 'SAMPLE_API_TOKEN',
        'vault' => 'ghp_abcdefghijklmnopqrstuvwxyz0123456789',
        'secret' => 'leak',
    ]))->toThrow(InvalidModuleSettingException::class);
});

it('invalidates the settings cache when a setting changes', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->setting('sample', 'title'))->toBe('Hello');
    expect(Cache::has(ModuleRegistry::CACHE_SETTINGS_PREFIX.'sample'))->toBeTrue();

    $registry->setSetting('sample', 'title', 'Updated');

    expect(Cache::has(ModuleRegistry::CACHE_SETTINGS_PREFIX.'sample'))->toBeFalse();
    expect($registry->setting('sample', 'title'))->toBe('Updated');
});

it('discovers the real shipped module manifests', function () {
    $registry = new ModuleRegistry(
        app(AuditLogger::class),
        app('cache.store'),
        base_path('modules'),
    );

    expect($registry->manifests()->keys()->sort()->values()->all())
        ->toBe(['communications', 'helpdesk', 'hr', 'microsoft-365', 'operations', 'people'])
        ->and($registry->enabled('people'))->toBeTrue()
        ->and($registry->enabled('hr'))->toBeTrue()
        ->and($registry->enabled('microsoft-365'))->toBeTrue()
        ->and($registry->manifest('microsoft-365')?->kind)->toBe('integration');
});
