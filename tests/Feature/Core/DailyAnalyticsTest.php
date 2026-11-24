<?php

use App\Core\Access\RoleName;
use App\Core\Analytics\AnalyticsDailyTotal;
use App\Core\Analytics\DailyMetricContributor;
use App\Core\Analytics\RecordDailyAnalytics;
use App\Core\Audit\AuditLogger;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\Operations\Models\ClockRecord;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates analytics_daily_totals with date, metric and count only', function () {
    expect(Schema::hasTable('analytics_daily_totals'))->toBeTrue();

    $columns = Schema::getColumnListing('analytics_daily_totals');

    expect($columns)->toContain('metric_date', 'metric', 'count')
        ->and($columns)->not->toContain('user_id', 'actor_id', 'ip_address', 'name', 'email');
});

it('records sign-in counts without storing people', function () {
    $day = now()->subDay()->startOfDay();
    $user = User::factory()->create();
    $user->assignRole(RoleName::Staff->value);

    $this->travelTo($day->copy()->addHours(9));
    app(AuditLogger::class)->record('auth.signed_in', $user, ['method' => 'cloudron'], $user);
    $this->travelTo($day->copy()->addHours(10));
    app(AuditLogger::class)->record('auth.signed_in', $user, ['method' => 'cloudron'], $user);
    $this->travelBack();

    Artisan::call('analytics:record-daily', ['--date' => $day->toDateString()]);

    $row = AnalyticsDailyTotal::query()
        ->whereDate('metric_date', $day->toDateString())
        ->where('metric', RecordDailyAnalytics::METRIC_SIGN_INS)
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->count)->toBe(2);

    expect(array_keys($row->getAttributes()))->not->toContain('user_id', 'actor_id', 'ip_address');
});

it('prunes daily totals older than 12 months', function () {
    $keepDate = now()->subMonthsNoOverflow(11)->toDateString();
    $dropDate = now()->subMonthsNoOverflow(13)->toDateString();

    AnalyticsDailyTotal::query()->create([
        'metric_date' => $keepDate,
        'metric' => 'sign_ins',
        'count' => 3,
    ]);
    AnalyticsDailyTotal::query()->create([
        'metric_date' => $dropDate,
        'metric' => 'old_sign_ins',
        'count' => 9,
    ]);

    expect(Artisan::call('analytics:record-daily', ['--date' => now()->subDay()->toDateString()]))->toBe(0);

    $metrics = AnalyticsDailyTotal::query()->pluck('metric')->all();
    expect($metrics)->not->toContain('old_sign_ins')
        ->and($metrics)->toContain('sign_ins');
});

it('skips module metric contributors when the module is switched off', function () {
    $calls = 0;
    $contributor = new class($calls) implements BelongsToModule, DailyMetricContributor
    {
        public function __construct(private int &$calls) {}

        public function moduleSlug(): string
        {
            return 'helpdesk';
        }

        public function metricsFor(Carbon $day): array
        {
            $this->calls++;

            return [['metric' => 'probe_helpdesk', 'count' => 7]];
        }
    };

    $this->app->instance('test.daily.probe', $contributor);
    $this->app->tag(['test.daily.probe'], 'intranet.daily-metrics');

    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    app(ModuleRegistry::class)->disable('helpdesk', 'Pause analytics test', $admin);

    Artisan::call('analytics:record-daily', ['--date' => now()->subDay()->toDateString()]);

    expect($calls)->toBe(0)
        ->and(AnalyticsDailyTotal::query()->where('metric', 'probe_helpdesk')->exists())->toBeFalse();
});

it('records helpdesk and clock-in totals when modules are on', function () {
    Helpdesk::seed();
    $day = now()->subDay()->startOfDay();
    $member = Helpdesk::member(RoleName::Staff);

    $ticket = Helpdesk::ticket($member);
    $ticket->forceFill([
        'created_at' => $day->copy()->addHour(),
        'updated_at' => $day->copy()->addHour(),
    ])->save();

    ClockRecord::query()->create([
        'user_id' => $member->id,
        'clock_in_at' => $day->copy()->addHours(8),
        'clock_out_at' => $day->copy()->addHours(16),
    ]);

    Artisan::call('analytics:record-daily', ['--date' => $day->toDateString()]);

    expect(AnalyticsDailyTotal::query()->where('metric', 'helpdesk_tickets_raised')->value('count'))->toBe(1)
        ->and(AnalyticsDailyTotal::query()->where('metric', 'clock_ins')->value('count'))->toBe(1);
});
