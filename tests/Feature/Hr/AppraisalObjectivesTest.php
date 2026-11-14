<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Hr\Models\AppraisalObjective;
use Modules\Hr\Services\AppraisalService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('saves objectives and dual sign-off on an appraisal', function () {
    $manager = User::factory()->create()->assignRole(RoleName::Supervisor->value);
    $member = User::factory()->create(['manager_id' => $manager->id])->assignRole(RoleName::Staff->value);
    $service = app(AppraisalService::class);
    $cycle = $service->openCycle('2026 annual', 2026, '2026-01-01', '2026-12-31');
    $appraisal = $service->startFor($cycle, $member, $manager);

    $this->actingAs($member)->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('hr.appraisals.objectives.store', $appraisal), [
        'title' => 'Lead a demo feed',
        'notes' => 'Fake objective only',
    ])->assertRedirect();

    expect(AppraisalObjective::query()->where('appraisal_id', $appraisal->id)->value('title'))
        ->toBe('Lead a demo feed');

    $this->post(route('hr.appraisals.sign', $appraisal), ['as' => 'member'])->assertRedirect();
    expect($appraisal->refresh()->member_signed_at)->not->toBeNull();

    $this->actingAs($manager)->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    $this->post(route('hr.appraisals.sign', $appraisal), ['as' => 'manager'])->assertRedirect();
    expect($appraisal->refresh()->bothSigned())->toBeTrue();
});
