<?php

use App\Core\Modules\Models\ModuleState;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists module schedules with the same cadence as before the move', function (string $command, string $expression) {
    $events = collect(Schedule::events())->filter(
        fn ($event) => str_contains($event->command ?? '', $command),
    );

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe($expression);
})->with([
    'people directory sync' => ['people:sync-directory', '15 2 * * *'],
    'people TOTP refresh' => ['people:refresh-totp-status', '30 2 * * *'],
    'comms event reminders' => ['comms:send-event-reminders', '*/5 * * * *'],
    'operations missed clock-outs' => ['operations:flag-missed-clock-outs', '*/15 * * * *'],
    'operations missed checklists' => ['operations:alert-missed-checklists', '*/5 * * * *'],
    'HR identity checks' => ['hr:remind-identity-check-expiry', '15 9 * * *'],
    'learning certificates' => ['learning:remind-certificate-expiry', '30 9 * * *'],
]);

it('keeps core schedules in routes/console.php only', function () {
    $console = file_get_contents(base_path('routes/console.php'));

    expect($console)->toContain('audit:prune')
        ->and($console)->toContain('system:check')
        ->and($console)->toContain('access-review:remind')
        ->and($console)->not->toContain('people:sync-directory')
        ->and($console)->not->toContain('comms:send-event-reminders');
});

it('skips a module schedule when that module is disabled', function () {
    $registry = app(ModuleRegistry::class);
    $admin = User::factory()->create();

    $events = collect(Schedule::events())->filter(
        fn ($event) => str_contains($event->command ?? '', 'people:sync-directory'),
    );

    expect($events->first()->filtersPass($this->app))->toBeTrue();

    $registry->disable('people', 'Skip people schedule', $admin);

    expect($events->first()->filtersPass($this->app))->toBeFalse()
        ->and(ModuleState::query()->where('slug', 'people')->exists())->toBeTrue();
});
