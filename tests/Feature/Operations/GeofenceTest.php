<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Operations\Models\Site;
use Modules\Operations\Services\Geofence;

uses(RefreshDatabase::class);

function geofencedSite(): Site
{
    return Site::query()->create(['name' => 'Reptile house', 'team' => 'Care', 'latitude' => 53.8008, 'longitude' => -1.5491, 'radius_metres' => 200]);
}

it('is off by default', function () {
    expect(config('operations.geofence.enabled'))->toBeFalse()
        ->and(config('operations.geofence.dpia_approved_at'))->toBeNull()
        ->and(app(Geofence::class)->isEnabled())->toBeFalse();
});

it('stays off when switched on without a recorded DPIA approval date', function () {
    config(['operations.geofence.enabled' => true, 'operations.geofence.dpia_approved_at' => null]);
    expect(app(Geofence::class)->isEnabled())->toBeFalse();

    config(['operations.geofence.dpia_approved_at' => 'not a date']);
    expect(app(Geofence::class)->isEnabled())->toBeFalse();

    config(['operations.geofence.dpia_approved_at' => now()->addMonth()->toDateString()]);
    expect(app(Geofence::class)->isEnabled())->toBeFalse();
});

it('stays off with an approval date but the switch off', function () {
    config(['operations.geofence.enabled' => false, 'operations.geofence.dpia_approved_at' => '2026-11-01']);

    expect(app(Geofence::class)->appliesTo(geofencedSite()))->toBeFalse();
});

it('applies only to sites with an area once approved and switched on', function () {
    config(['operations.geofence.enabled' => true, 'operations.geofence.dpia_approved_at' => '2026-11-01']);
    $plain = Site::query()->create(['name' => 'Office', 'team' => 'Office']);

    expect(app(Geofence::class)->appliesTo(geofencedSite()))->toBeTrue()
        ->and(app(Geofence::class)->appliesTo($plain))->toBeFalse();
});

it('passes inside the radius and fails outside it or without a position', function () {
    $site = geofencedSite();
    $geofence = app(Geofence::class);

    expect($geofence->check($site, 53.8010, -1.5495))->toBe(Geofence::PASS)
        ->and($geofence->check($site, 53.8100, -1.5491))->toBe(Geofence::FAIL)
        ->and($geofence->check($site, null, null))->toBe(Geofence::FAIL);
});

it('keeps the DPIA in the repository with an owner sign-off section', function () {
    $dpia = file_get_contents(base_path('docs/dpia/clock-in-geofence.md'));

    expect($dpia)->toContain('Step 7: Sign-off and outcomes')
        ->toContain('OPERATIONS_GEOFENCE_DPIA_APPROVED_AT')
        ->toContain('Bambie Murphy, Director');
});
