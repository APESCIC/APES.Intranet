<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPages;
use App\Core\Legal\LegalPageVersionStatus;
use App\Core\Legal\LegalPageVisibility;
use App\Models\LegalPageVersion;
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
function adminLegalFreshSignIn(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

function legalPagesManager(): User
{
    return User::factory()->create()->assignRole(RoleName::Legal->value);
}

it('forbids every admin legal route without legal.pages.manage', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($page, '<h2>Live</h2><p>Published.</p>');
    app(LegalPages::class)->openDraft($page);

    $session = adminLegalFreshSignIn();
    $acting = $this->actingAs($staff)->withSession($session);

    $acting->get(route('admin.legal.footer-links.index'))->assertForbidden();
    $acting->get(route('admin.legal.pages.index'))->assertForbidden();
    $acting->get(route('admin.legal.pages.edit', $page))->assertForbidden();
    $acting->get(route('admin.legal.pages.preview', $page))->assertForbidden();
    $acting->post(route('admin.legal.pages.draft', $page), [
        'body' => '<p>Draft</p>',
        'visibility' => LegalPageVisibility::Everyone,
    ])->assertForbidden();
    $acting->post(route('admin.legal.pages.publish', $page), ['mode' => 'now'])->assertForbidden();
    $acting->get(route('admin.legal.pages.history', $page))->assertForbidden();
    $acting->get(route('admin.legal.pages.compare', ['legalPage' => $page, 'from' => 1, 'to' => 2]))->assertForbidden();
    $version = $page->versions()->first();
    $acting->post(route('admin.legal.pages.restore', [$page, $version]))->assertForbidden();
});

it('renders admin preview with the same pipeline as the public legal page', function () {
    $manager = legalPagesManager();
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    $body = '<h2>Overview</h2><p>Same <strong>text</strong> for both.</p>';
    LegalPageFixtures::publishVersion($page, $body);

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, $body, 'Wording refresh.');

    $public = $this->get(route('privacy'))
        ->assertOk();

    $preview = $this->actingAs($manager)
        ->withSession(adminLegalFreshSignIn())
        ->get(route('admin.legal.pages.preview', $page))
        ->assertOk();

    $processed = $legalPages->bodyWithHeadingIds($legalPages->draftFor($page)->body);
    expect($processed)->toContain('id="overview"')
        ->and($preview->getContent())->toContain('id="overview"')
        ->and($public->getContent())->toContain('id="overview"')
        ->and($preview->getContent())->toContain('Same <strong>text</strong> for both.')
        ->and($public->getContent())->toContain('Same <strong>text</strong> for both.');
});

it('never shows a draft on the public legal route', function () {
    $page = LegalPageFixtures::page('terms', 'Terms of use');
    LegalPageFixtures::publishVersion($page, '<h2>Live</h2><p>Published wording only.</p>');

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, '<h2>Draft</h2><p>Must stay hidden.</p>', 'Secret draft.');

    $this->get(route('legal.terms'))
        ->assertOk()
        ->assertSee('Published wording only.')
        ->assertDontSee('Must stay hidden.');
});

it('keeps only one open draft per legal page', function () {
    $manager = legalPagesManager();
    $page = LegalPageFixtures::page('accessibility', 'Accessibility statement');
    LegalPageFixtures::publishVersion($page, '<p>Published.</p>');

    $legalPages = app(LegalPages::class);
    $first = $legalPages->openDraft($page);
    $second = $legalPages->openDraft($page);

    expect($first->id)->toBe($second->id);

    $this->actingAs($manager)
        ->withSession(adminLegalFreshSignIn())
        ->get(route('admin.legal.pages.edit', $page))
        ->assertOk();

    expect(LegalPageVersion::query()
        ->where('legal_page_id', $page->id)
        ->where('status', LegalPageVersionStatus::Draft)
        ->count())->toBe(1);

    $this->actingAs($manager)
        ->withSession(adminLegalFreshSignIn())
        ->post(route('admin.legal.pages.draft', $page), [
            'body' => '<h2>Updated draft</h2><p>Still one draft.</p>',
            'change_summary' => 'Tweaks',
            'visibility' => LegalPageVisibility::Everyone,
        ])
        ->assertRedirect(route('admin.legal.pages.edit', $page));

    expect(LegalPageVersion::query()
        ->where('legal_page_id', $page->id)
        ->where('status', LegalPageVersionStatus::Draft)
        ->count())->toBe(1);

    expect(AuditLog::query()
        ->where('event', LegalAuditEvents::PAGE_DRAFT_SAVED)
        ->exists())->toBeTrue();
});
