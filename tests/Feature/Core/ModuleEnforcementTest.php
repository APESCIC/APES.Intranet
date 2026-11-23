<?php

use App\Core\Audit\AuditLogger;
use App\Core\Dashboard\DashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\Concerns\BelongsToModuleSlug;
use App\Core\Modules\Models\ModuleState;
use App\Core\Modules\ModuleAware;
use App\Core\Modules\ModuleDisabledException;
use App\Core\Modules\ModuleGuards;
use App\Core\Modules\ModuleKind;
use App\Core\Modules\ModuleRegistry;
use App\Core\Navigation\Menu;
use App\Core\Navigation\MenuContributor;
use App\Core\Navigation\MenuMode;
use App\Core\Navigation\NavGroup;
use App\Core\Navigation\NavItem;
use App\Core\Search\SearchProvider;
use App\Core\Search\SearchProviders;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    fakeCloudron($this);

    $this->modulesPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'apes-modules-enforce-'.uniqid('', true);
    File::ensureDirectoryExists($this->modulesPath.'/pilot');
    File::put($this->modulesPath.'/pilot/module.php', <<<'PHP'
<?php

return [
    'slug' => 'pilot',
    'name' => 'Pilot',
    'kind' => 'module',
    'version' => '0.1.0',
    'description' => 'Fake module for disable enforcement tests.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => [],
    'settings' => [],
];
PHP);

    File::ensureDirectoryExists($this->modulesPath.'/hook');
    File::put($this->modulesPath.'/hook/module.php', <<<'PHP'
<?php

return [
    'slug' => 'hook',
    'name' => 'Hook',
    'kind' => 'integration',
    'version' => '0.1.0',
    'description' => 'Fake integration for webhook 503 tests.',
    'default_enabled' => true,
    'depends_on' => [],
    'cloudron_addons' => [],
    'settings' => [],
];
PHP);

    $this->app->singleton(ModuleRegistry::class, fn ($app) => new ModuleRegistry(
        $app->make(AuditLogger::class),
        $app->make('cache.store'),
        $this->modulesPath,
    ));

    Route::middleware(['web', 'auth', 'module:pilot'])
        ->get('/pilot/page', fn () => response('pilot-ok'))
        ->name('pilot.page');

    Route::middleware(['web', 'module:hook,webhook'])
        ->post('/webhooks/hook', fn () => response('hook-ok'))
        ->name('webhooks.hook');

    $this->menuContributor = new class implements BelongsToModule, MenuContributor
    {
        public function moduleSlug(): string
        {
            return 'pilot';
        }

        public function groups(User $user, MenuMode $mode): array
        {
            return [
                new NavGroup('pilot', 'Pilot', [
                    NavItem::route('pilot-page', 'Pilot page', 'flask-conical', 'intranet.access', 'pilot.page', null, 'pilot'),
                ]),
            ];
        }
    };

    $this->dashboardContributor = new class implements BelongsToModule, DashboardCardContributor
    {
        public function moduleSlug(): string
        {
            return 'pilot';
        }

        public function cards(User $user): array
        {
            return [[
                'key' => 'pilot-card',
                'title' => 'Pilot card',
                'body' => 'Only when enabled.',
                'href' => '/pilot/page',
            ]];
        }
    };

    $this->searchProvider = new class implements SearchProvider
    {
        public function moduleSlug(): string
        {
            return 'pilot';
        }

        public function search(User $user, string $query): array
        {
            return [[
                'title' => 'Pilot hit',
                'url' => '/pilot/page',
                'snippet' => $query,
            ]];
        }
    };

    $this->app->instance('test.pilot.menu', $this->menuContributor);
    $this->app->tag(['test.pilot.menu'], 'intranet.menu');
    $this->app->instance('test.pilot.dashboard', $this->dashboardContributor);
    $this->app->tag(['test.pilot.dashboard'], 'intranet.dashboard');
    $this->app->instance('test.pilot.search', $this->searchProvider);
    $this->app->tag(['test.pilot.search'], 'intranet.search');
});

afterEach(function () {
    if (isset($this->modulesPath) && File::isDirectory($this->modulesPath)) {
        File::deleteDirectory($this->modulesPath);
    }
});

it('returns 404 for module pages when the module is disabled and restores access when re-enabled', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();
    signInWithCloudron($this, ['intranet.staff']);

    $this->get('/pilot/page')->assertOk()->assertSee('pilot-ok');

    $registry->disable('pilot', 'Pause for test', $admin);
    $this->get('/pilot/page')->assertNotFound();

    expect(ModuleState::query()->where('slug', 'pilot')->exists())->toBeTrue();

    $registry->enable('pilot', 'Resume after test', $admin);
    $this->get('/pilot/page')->assertOk()->assertSee('pilot-ok');
});

it('returns 503 with Retry-After for integration webhooks when disabled', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    expect($registry->manifest('hook')?->kind)->toBe(ModuleKind::Integration);

    $this->post('/webhooks/hook')->assertOk();

    $registry->disable('hook', 'Supplier down', $admin);

    $this->post('/webhooks/hook')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '300');
});

it('hides hard-coded and contributed menu items for a disabled module', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();
    signInWithCloudron($this, ['intranet.staff']);
    $user = User::where('email', 'alex@example.test')->firstOrFail();

    $labels = collect(Menu::for($user))->flatMap(fn (NavGroup $g) => collect($g->items)->pluck('label'));
    expect($labels)->toContain('Pilot page');

    $registry->disable('pilot', 'Hide nav', $admin);

    $labels = collect(Menu::for($user))->flatMap(fn (NavGroup $g) => collect($g->items)->pluck('label'));
    expect($labels)->not->toContain('Pilot page');
});

it('skips dashboard cards for a disabled module', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();
    $user = User::factory()->create();

    $cards = [];
    foreach (app()->tagged('intranet.dashboard') as $contributor) {
        if ($contributor instanceof BelongsToModule && ! $registry->enabled($contributor->moduleSlug())) {
            continue;
        }
        if ($contributor instanceof DashboardCardContributor) {
            array_push($cards, ...$contributor->cards($user));
        }
    }
    expect(collect($cards)->pluck('key'))->toContain('pilot-card');

    $registry->disable('pilot', 'Hide cards', $admin);

    $cards = [];
    foreach (app()->tagged('intranet.dashboard') as $contributor) {
        if ($contributor instanceof BelongsToModule && ! $registry->enabled($contributor->moduleSlug())) {
            continue;
        }
        if ($contributor instanceof DashboardCardContributor) {
            array_push($cards, ...$contributor->cards($user));
        }
    }
    expect(collect($cards)->pluck('key'))->not->toContain('pilot-card');
});

it('skips search providers for a disabled module', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();
    $user = User::factory()->create();
    $search = app(SearchProviders::class);

    expect($search->search($user, 'hello'))->toHaveCount(1);

    $registry->disable('pilot', 'Hide search', $admin);

    expect($search->search($user, 'hello'))->toHaveCount(0);
});

it('skips schedules when the module is disabled via whenEnabled', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    Schedule::command('inspire')->everyMinute()->when(ModuleGuards::whenEnabled('pilot'));

    $events = collect(Schedule::events())->filter(
        fn ($event) => str_contains($event->command ?? '', 'inspire'),
    );

    expect($events)->not->toBeEmpty()
        ->and($events->first()->filtersPass($this->app))->toBeTrue();

    $registry->disable('pilot', 'Skip schedule', $admin);

    expect($events->first()->filtersPass($this->app))->toBeFalse();
});

it('refuses new job dispatch and reports pause or cancel for queued jobs', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    $job = new class implements ModuleAware
    {
        use BelongsToModuleSlug;

        public function moduleSlug(): string
        {
            return 'pilot';
        }
    };

    ModuleGuards::assertCanDispatch('pilot');
    expect(ModuleGuards::handleDisabledQueuedJob($job))->toBe('run');

    $registry->disable('pilot', 'Stop jobs', $admin);

    expect(fn () => ModuleGuards::assertCanDispatch('pilot'))
        ->toThrow(ModuleDisabledException::class)
        ->and(ModuleGuards::handleDisabledQueuedJob($job))->toBe('pause');

    $cancellable = new class implements ModuleAware
    {
        use BelongsToModuleSlug;

        public function moduleSlug(): string
        {
            return 'pilot';
        }

        public function isCancellableWhenModuleDisabled(): bool
        {
            return true;
        }
    };

    expect(ModuleGuards::handleDisabledQueuedJob($cancellable))->toBe('cancel');
});

it('no-ops listeners and refuses notifications and outbound integration calls when disabled', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    expect(ModuleGuards::listenerShouldRun('pilot'))->toBeTrue();
    ModuleGuards::assertCanNotify('pilot');
    ModuleGuards::assertCanCallIntegration('hook');

    $registry->disable('pilot', 'Stop listeners', $admin);
    $registry->disable('hook', 'Stop outbound', $admin);

    expect(ModuleGuards::listenerShouldRun('pilot'))->toBeFalse();
    expect(fn () => ModuleGuards::assertCanNotify('pilot'))->toThrow(ModuleDisabledException::class);

    Log::spy();
    expect(fn () => ModuleGuards::assertCanCallIntegration('hook'))->toThrow(ModuleDisabledException::class);
    Log::shouldHaveReceived('warning')->once();
});

it('never allows Core to be disabled', function () {
    $registry = app(ModuleRegistry::class);

    expect($registry->isCoreSlug('core'))->toBeTrue()
        ->and(fn () => $registry->disable('core', 'Should fail'))
        ->toThrow(InvalidArgumentException::class, 'Core cannot be disabled.');
});
