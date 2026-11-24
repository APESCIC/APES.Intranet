<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPages;
use App\Models\LegalAcceptance;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegalPageFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function acceptanceReportFreshSignIn(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

it('shows matching accepted and not yet counts for the current Terms version', function () {
    $page = LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Please accept.</p>', changeSummary: 'Version 1.');
    $version = app(LegalPages::class)->currentVersion($page);
    expect($version)->not->toBeNull();

    $acceptedUser = User::factory()->create()->assignRole(RoleName::Staff->value);
    $acceptedUser->assignRole(RoleName::Legal->value);
    $pendingUser = User::factory()->create()->assignRole(RoleName::Staff->value);

    LegalAcceptance::query()->create([
        'user_id' => $acceptedUser->id,
        'legal_page_version_id' => $version->id,
        'accepted_at' => now(),
    ]);

    $response = $this->actingAs($acceptedUser)
        ->withSession(acceptanceReportFreshSignIn())
        ->get(route('admin.legal.acceptance.index'));

    $response->assertOk()
        ->assertViewHas('accepted_count', 1)
        ->assertViewHas('pending_count', 1)
        ->assertSee($acceptedUser->name)
        ->assertSee($pendingUser->name);
});

it('audits legal.acceptance.exported when exporting CSV', function () {
    $page = LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Please accept.</p>', changeSummary: 'Version 1.');
    $version = app(LegalPages::class)->currentVersion($page);
    expect($version)->not->toBeNull();

    $manager = User::factory()->create()->assignRole(RoleName::Staff->value);
    $manager->assignRole(RoleName::Legal->value);
    LegalAcceptance::query()->create([
        'user_id' => $manager->id,
        'legal_page_version_id' => $version->id,
        'accepted_at' => now(),
    ]);

    $this->actingAs($manager)
        ->withSession(acceptanceReportFreshSignIn())
        ->get(route('admin.legal.acceptance.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $audit = AuditLog::query()->where('event', LegalAuditEvents::ACCEPTANCE_EXPORTED)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($manager->id)
        ->and($audit->context)->toMatchArray([
            'version' => $version->number,
            'count' => 1,
            'filter' => 'all',
        ]);
});

it('forbids staff without legal.pages.manage from the report and export', function () {
    $page = LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Please accept.</p>', changeSummary: 'Version 1.');
    $version = app(LegalPages::class)->currentVersion($page);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);
    LegalAcceptance::query()->create([
        'user_id' => $staff->id,
        'legal_page_version_id' => $version?->id,
        'accepted_at' => now(),
    ]);
    $acting = $this->actingAs($staff)->withSession(acceptanceReportFreshSignIn());

    $acting->get(route('admin.legal.acceptance.index'))->assertForbidden();
    $acting->get(route('admin.legal.acceptance.export'))->assertForbidden();
});
