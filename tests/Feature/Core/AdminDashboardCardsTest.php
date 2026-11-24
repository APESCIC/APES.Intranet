<?php

use App\Core\Access\RoleName;
use App\Core\Analytics\AnalyticsDailyTotal;
use App\Core\Analytics\RecordDailyAnalytics;
use App\Models\User;
use Database\Seeders\HelpdeskPermissionsSeeder;
use Database\Seeders\Microsoft365PermissionsSeeder;
use Database\Seeders\OperationsPermissionsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Operations\Models\ClockRecord;
use Spatie\Permission\PermissionRegistrar;
use Tests\Support\Helpdesk;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(HelpdeskPermissionsSeeder::class);
    $this->seed(OperationsPermissionsSeeder::class);
    $this->seed(Microsoft365PermissionsSeeder::class);
});

it('shows the first set of admin analytics cards for a full admin', function () {
    Helpdesk::seed();
    $this->seed(OperationsPermissionsSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    AnalyticsDailyTotal::query()->create([
        'metric_date' => now()->subDay()->toDateString(),
        'metric' => RecordDailyAnalytics::METRIC_SIGN_INS,
        'count' => 4,
    ]);

    $member = Helpdesk::member(RoleName::Staff);
    Helpdesk::ticket($member);
    ClockRecord::query()->create([
        'user_id' => $member->id,
        'clock_in_at' => now()->subHour(),
        'clock_out_at' => null,
    ]);

    $html = $this->actingAs($admin)->get(route('admin.overview'))->assertOk()->getContent();

    expect($html)
        ->toContain('data-admin-card="core-sign-ins"')
        ->toContain('data-admin-card="core-module-health"')
        ->toContain('data-admin-card="core-recent-activity"')
        ->toContain('data-admin-card="core-versions"')
        ->toContain('data-admin-card="helpdesk-admin-by-status"')
        ->toContain('data-admin-card="helpdesk-admin-past-sla"')
        ->toContain('data-admin-card="operations-admin-clocked-in"')
        ->toContain('data-admin-card="operations-admin-rota-cover"')
        ->toContain('sg-admin-dashboard-chart')
        ->toContain('1 person is clocked in');
});

it('matches clocked-in count on the admin card to open clock records', function () {
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Admin->value);
    $member = User::factory()->create();
    $member->assignRole(RoleName::Staff->value);

    ClockRecord::query()->create([
        'user_id' => $member->id,
        'clock_in_at' => now()->subMinutes(30),
        'clock_out_at' => null,
    ]);

    $open = ClockRecord::query()->whereNull('clock_out_at')->count();

    $this->actingAs($admin)->get(route('admin.overview'))
        ->assertOk()
        ->assertSee(trans_choice(':count person is clocked in.|:count people are clocked in.', $open));
});
