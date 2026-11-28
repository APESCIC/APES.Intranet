<?php

use App\Core\Access\RoleName;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\ShiftTemplate;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    Operations::seedRoles();
    $this->site = Operations::site();
    $this->week = Operations::week($this->site, RotaWeek::STATUS_DRAFT, CarbonImmutable::parse('2026-11-16'));
    $this->shift = Operations::shift($this->week, null, CarbonImmutable::parse('2026-11-16 09:00:00'));
    $this->template = ShiftTemplate::query()->create([
        'site_id' => $this->site->id,
        'name' => 'Morning feed',
        'role' => 'Animal care',
        'weekday' => 1,
        'starts_at' => '08:00',
        'ends_at' => '12:00',
        'active' => true,
    ]);
});

it('lets supervisors management and admin manage the rota for the teams they should see', function (RoleName $role) {
    $manager = Operations::member($role);
    $otherSite = Operations::site('Office', 'Front desk');
    $otherWeek = Operations::week($otherSite, RotaWeek::STATUS_DRAFT, CarbonImmutable::parse('2026-11-16'));

    $this->actingAs($manager)->get(route('operations.rota.weeks.show', $this->week))->assertOk();
    $this->actingAs($manager)->get(route('operations.rota.templates.index'))->assertOk();
    $this->actingAs($manager)->post(route('operations.rota.weeks.shifts.store', $this->week), [
        'role' => 'One-off',
        'starts_at' => '2026-11-17T09:00',
        'ends_at' => '2026-11-17T17:00',
    ])->assertRedirect();
    $this->actingAs($manager)->post(route('operations.rota.weeks.copy', $this->week))->assertRedirect();

    if ($role === RoleName::Supervisor) {
        $this->actingAs($manager)->get(route('operations.rota.weeks.show', $otherWeek))->assertForbidden();
    } else {
        $this->actingAs($manager)->get(route('operations.rota.weeks.show', $otherWeek))->assertOk();
    }
})->with([
    RoleName::Supervisor,
    RoleName::Management,
    RoleName::Admin,
]);

it('forbids staff volunteer student hr and legal from managing the rota table', function (RoleName $role) {
    $member = Operations::member($role);

    $this->actingAs($member)->get(route('operations.rota.weeks.show', $this->week))->assertForbidden();
    $this->actingAs($member)->get(route('operations.rota.templates.index'))->assertForbidden();
    $this->actingAs($member)->post(route('operations.rota.weeks.shifts.store', $this->week), [
        'role' => 'Forbidden',
        'starts_at' => '2026-11-17T09:00',
        'ends_at' => '2026-11-17T17:00',
    ])->assertForbidden();
    $this->actingAs($member)->post(route('operations.rota.weeks.copy', $this->week))->assertForbidden();
    $this->actingAs($member)->patch(route('operations.rota.templates.update', $this->template), [
        'name' => 'Nope',
        'role' => 'Nope',
        'weekday' => 1,
        'starts_at' => '09:00',
        'ends_at' => '17:00',
    ])->assertForbidden();
    $this->actingAs($member)->get(route('operations.rota.index'))->assertOk();
})->with([
    RoleName::Staff,
    RoleName::Volunteer,
    RoleName::Student,
    RoleName::Hr,
    RoleName::Legal,
]);
