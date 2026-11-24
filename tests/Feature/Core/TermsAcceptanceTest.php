<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPages;
use App\Models\LegalAcceptance;
use App\Models\LegalNoticeDismissal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegalPageFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function staffUser(): User
{
    return User::factory()->create()->assignRole(RoleName::Staff->value);
}

it('shows the acceptance prompt but still allows legal pages and sign-out', function () {
    LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Please read.</p>', changeSummary: 'Version 1.');
    $privacy = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($privacy, '<h2>Privacy</h2><p>Notice.</p>');
    $cookies = LegalPageFixtures::page('cookies', 'Cookies');
    LegalPageFixtures::publishVersion($cookies, '<h2>Cookies</h2><p>Notice.</p>');
    $user = staffUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('legal.accept.show'));

    $this->actingAs($user)
        ->get(route('legal.accept.show'))
        ->assertOk()
        ->assertSee('Accept Terms of use');

    $this->actingAs($user)->get(route('legal.terms'))->assertOk();
    $this->actingAs($user)->get(route('privacy'))->assertOk();
    $this->actingAs($user)->get(route('legal.cookies'))->assertOk();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));
});

it('records acceptance with a timestamp and audits legal.terms.accepted', function () {
    $page = LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Accept me.</p>', changeSummary: 'Version 1.');
    $user = staffUser();
    $version = app(LegalPages::class)->currentVersion($page);

    $this->actingAs($user)
        ->post(route('legal.accept.store'))
        ->assertRedirect(route('dashboard'));

    $acceptance = LegalAcceptance::query()
        ->where('user_id', $user->id)
        ->where('legal_page_version_id', $version?->id)
        ->first();

    expect($acceptance)->not->toBeNull()
        ->and($acceptance->accepted_at)->not->toBeNull();

    $audit = AuditLog::query()->where('event', LegalAuditEvents::TERMS_ACCEPTED)->first();
    expect($audit)->not->toBeNull()
        ->and($audit->actor_id)->toBe($user->id)
        ->and($audit->context)->toMatchArray([
            'legal_page_id' => $page->id,
            'version' => 1,
        ]);
});

it('does not prompt after a non-material change to the Terms of use', function () {
    $page = LegalPageFixtures::publishTerms('<h2>v1</h2><p>First.</p>', changeSummary: 'Version 1.');
    $user = staffUser();
    $legalPages = app(LegalPages::class);

    $this->actingAs($user)->post(route('legal.accept.store'))->assertRedirect(route('dashboard'));
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $versionTwo = $legalPages->createDraft(
        $page,
        '<h2>v2</h2><p>Typo fix.</p>',
        'Minor wording tidy.',
        materialChange: false,
    );
    $legalPages->publish($versionTwo);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    expect($legalPages->currentVersion($page)?->number)->toBe(2);
});

it('shows Privacy and Cookies updated notes once per person', function () {
    $privacy = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($privacy, '<h2>v1</h2><p>Privacy.</p>', changeSummary: 'First publication.');
    LegalPageFixtures::publishVersion($privacy, '<h2>v2</h2><p>Updated privacy.</p>', changeSummary: 'Clarified retention.');

    $cookies = LegalPageFixtures::page('cookies', 'Cookies');
    LegalPageFixtures::publishVersion($cookies, '<h2>v1</h2><p>Cookies.</p>', changeSummary: 'First publication.');
    LegalPageFixtures::publishVersion($cookies, '<h2>v2</h2><p>Updated cookies.</p>', changeSummary: 'Register refresh.');

    LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Terms.</p>', changeSummary: 'Version 1.');
    $user = staffUser();

    $this->actingAs($user)->post(route('legal.accept.store'));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Privacy notice has been updated')
        ->assertSee('Cookies has been updated');

    $this->actingAs($user)
        ->post(route('legal.notice.dismiss', $privacy))
        ->assertRedirect();

    expect(LegalNoticeDismissal::query()->where('user_id', $user->id)->where('legal_page_id', $privacy->id)->exists())
        ->toBeTrue();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Privacy notice has been updated')
        ->assertSee('Cookies has been updated');

    $this->actingAs($user)->post(route('legal.notice.dismiss', $cookies));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Privacy notice has been updated')
        ->assertDontSee('Cookies has been updated');
});

it('respects the emergency off-switch for the acceptance prompt', function () {
    LegalPageFixtures::publishTerms('<h2>Terms</h2><p>Please read.</p>', changeSummary: 'Version 1.');
    config(['legal.acceptance.enabled' => false]);
    $user = staffUser();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});
