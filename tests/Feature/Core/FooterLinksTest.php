<?php

use App\Core\Access\RoleName;
use App\Core\Legal\FooterLinkGroup;
use App\Core\Legal\FooterLinkPresenter;
use App\Core\Legal\FooterLinks;
use App\Core\Legal\FooterLinkTargetType;
use App\Core\Legal\LegalPageVisibility;
use App\Core\Modules\ModuleRegistry;
use App\Models\FooterLink;
use App\Models\User;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, LegalPagesSeeder::class]);
});

function footerLinksManager(): User
{
    return User::factory()->create()->assignRole(RoleName::Legal->value);
}

/**
 * @return array<string, mixed>
 */
function footerLinksFreshSignIn(): array
{
    return ['intranet.auth_time' => now()->getTimestamp()];
}

it('forbids footer links admin without legal.pages.manage', function () {
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)
        ->withSession(footerLinksFreshSignIn())
        ->get(route('admin.legal.footer-links.index'))
        ->assertForbidden();
});

it('cannot remove or hide locked footer links', function () {
    $manager = footerLinksManager();
    $privacy = FooterLink::query()->where('target_value', 'privacy')->firstOrFail();

    expect($privacy->locked)->toBeTrue();

    $this->actingAs($manager)
        ->withSession(footerLinksFreshSignIn())
        ->delete(route('admin.legal.footer-links.destroy', $privacy))
        ->assertForbidden();

    $this->actingAs($manager)
        ->withSession(footerLinksFreshSignIn())
        ->put(route('admin.legal.footer-links.update', $privacy), [
            'group' => FooterLinkGroup::Legal,
            'label' => $privacy->label,
            'icon' => null,
            'target_type' => $privacy->target_type,
            'target_value' => $privacy->target_value,
            'visibility' => LegalPageVisibility::SignedIn,
            'module_slug' => null,
        ])
        ->assertStatus(422);
});

it('hides a footer link when its module is turned off', function () {
    FooterLink::query()->create([
        'group' => FooterLinkGroup::About,
        'label' => 'Helpdesk',
        'target_type' => FooterLinkTargetType::Route,
        'target_value' => 'dashboard',
        'visibility' => LegalPageVisibility::Everyone,
        'sort_order' => 10,
        'locked' => false,
        'module_slug' => 'helpdesk',
    ]);

    $registry = app(ModuleRegistry::class);
    $manager = footerLinksManager();
    $registry->disable('helpdesk', 'Footer link test', $manager);

    $visible = app(FooterLinks::class)->forVisitor($manager);

    expect($visible->pluck('label'))->not->toContain('Helpdesk');
});

it('reorders footer links with move up and move down', function () {
    $manager = footerLinksManager();

    $links = FooterLink::query()
        ->where('group', FooterLinkGroup::Legal)
        ->orderBy('sort_order')
        ->get();

    expect($links->count())->toBeGreaterThan(1);
    $second = $links[1];

    $this->actingAs($manager)
        ->withSession(footerLinksFreshSignIn())
        ->post(route('admin.legal.footer-links.reorder', $second), ['direction' => 'up'])
        ->assertRedirect(route('admin.legal.footer-links.index'));

    $reordered = FooterLink::query()
        ->where('group', FooterLinkGroup::Legal)
        ->orderBy('sort_order')
        ->pluck('id')
        ->all();

    expect($reordered[0])->toBe($second->id);
});

it('marks external footer links as opening another site', function () {
    $link = FooterLink::query()->create([
        'group' => FooterLinkGroup::About,
        'label' => 'APES website',
        'target_type' => FooterLinkTargetType::Url,
        'target_value' => 'https://apes.org.uk',
        'visibility' => LegalPageVisibility::Everyone,
        'sort_order' => 10,
        'locked' => false,
        'module_slug' => null,
    ]);

    $html = app(FooterLinkPresenter::class)->anchor($link);

    expect($html)
        ->toContain('rel="noopener"')
        ->toContain('target="_blank"')
        ->toContain('opens another site');
});

it('seeds locked privacy and cookies for everyone', function () {
    $guestLinks = app(FooterLinks::class)->forVisitor(null);

    expect($guestLinks->pluck('target_value'))
        ->toContain('privacy', 'cookies');
});

it('allows managers to open the footer links screen', function () {
    $this->actingAs(footerLinksManager())
        ->withSession(footerLinksFreshSignIn())
        ->get(route('admin.legal.footer-links.index'))
        ->assertOk()
        ->assertSee('Footer links')
        ->assertSee('data-move-up', false)
        ->assertSee('Privacy notice');
});
