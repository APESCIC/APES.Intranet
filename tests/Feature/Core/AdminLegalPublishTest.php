<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPages;
use App\Core\Legal\LegalPageVersionStatus;
use App\Models\LegalPageVersion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\LegalPageFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function publishAdminSession(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

function publishLegalManager(): User
{
    return User::factory()->create()->assignRole(RoleName::Legal->value);
}

it('records legal.page.published with version number and in effect date when publishing', function () {
    $manager = publishLegalManager();
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($page, '<p>Live</p>');

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, '<p>Second edition.</p>', 'Refresh wording.');

    $inEffect = Carbon::parse('2028-06-01', config('app.timezone'))->startOfDay();

    $this->actingAs($manager)
        ->withSession(publishAdminSession())
        ->post(route('admin.legal.pages.publish', $page), [
            'mode' => 'schedule',
            'in_effect_at' => $inEffect->format('Y-m-d'),
        ])
        ->assertRedirect(route('admin.legal.pages.index'));

    $entry = AuditLog::query()
        ->where('event', LegalAuditEvents::PAGE_PUBLISHED)
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->context)->toMatchArray([
            'version_number' => 2,
            'in_effect_at' => $inEffect->toIso8601String(),
        ]);

    $published = LegalPageVersion::query()
        ->where('legal_page_id', $page->id)
        ->where('number', 2)
        ->first();

    expect($published)->not->toBeNull()
        ->and($published->status)->toBe(LegalPageVersionStatus::Published)
        ->and($published->in_effect_at?->toDateString())->toBe($inEffect->toDateString());
});

it('restores a published version into the open draft without changing published history', function () {
    $manager = publishLegalManager();
    $page = LegalPageFixtures::page('terms', 'Terms of use');
    $v1 = LegalPageFixtures::publishVersion($page, '<p>Original terms wording.</p>', changeSummary: 'First publication.');

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, '<p>Revised terms wording.</p>', 'Major update.');
    $legalPages->publish($draft, $manager);

    expect(LegalPageVersion::query()
        ->where('legal_page_id', $page->id)
        ->where('status', LegalPageVersionStatus::Published)
        ->count())->toBe(2);

    $this->actingAs($manager)
        ->withSession(publishAdminSession())
        ->post(route('admin.legal.pages.restore', [$page, $v1]))
        ->assertRedirect(route('admin.legal.pages.edit', $page));

    expect(LegalPageVersion::query()
        ->where('legal_page_id', $page->id)
        ->where('status', LegalPageVersionStatus::Published)
        ->count())->toBe(2);

    $openDraft = $legalPages->draftFor($page);
    expect($openDraft)->not->toBeNull()
        ->and(strip_tags($openDraft->body))->toContain('Original terms wording')
        ->and($openDraft->status)->toBe(LegalPageVersionStatus::Draft);

    expect(AuditLog::query()
        ->where('event', LegalAuditEvents::PAGE_RESTORED)
        ->exists())->toBeTrue();
});

it('marks insertions and deletions when comparing two versions', function () {
    $manager = publishLegalManager();
    $page = LegalPageFixtures::page('accessibility', 'Accessibility statement');
    $v1 = LegalPageFixtures::publishVersion($page, '<p>Alpha beta gamma.</p>');

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, '<p>Alpha delta gamma.</p>');

    $diff = $legalPages->compare($v1, $draft);

    expect(collect($diff)->pluck('type')->all())->toContain('insert', 'delete');

    $this->actingAs($manager)
        ->withSession(publishAdminSession())
        ->get(route('admin.legal.pages.compare', [
            'legalPage' => $page,
            'from' => $v1->id,
            'to' => $draft->id,
        ]))
        ->assertOk()
        ->assertSee('<ins', false)
        ->assertSee('<del', false)
        ->assertSee('delta', false)
        ->assertSee('beta', false);
});

it('lists earlier published everyone versions on the public versions page', function () {
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($page, '<p>First privacy wording for everyone.</p>', changeSummary: 'Version one.');

    $legalPages = app(LegalPages::class);
    $draft = $legalPages->openDraft($page);
    $legalPages->updateDraft($draft, '<p>Second privacy wording for everyone.</p>', 'Version two.');
    $legalPages->publish($draft);

    $this->get(route('legal.versions', $page->slug))
        ->assertOk()
        ->assertSee('Version 1')
        ->assertSee('Version 2')
        ->assertSee('Version one.')
        ->assertSee('First privacy wording')
        ->assertSee('Second privacy wording');
});
