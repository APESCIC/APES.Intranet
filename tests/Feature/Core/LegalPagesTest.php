<?php

use App\Core\Legal\Exceptions\PublishedLegalVersionException;
use App\Core\Legal\LegalPages;
use App\Core\Legal\LegalPageVisibility;
use App\Core\Support\HtmlSanitizer;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\LegalPageFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets guests open everyone legal pages when a published version is in effect', function () {
    foreach ([
        ['slug' => 'privacy', 'title' => 'Privacy notice', 'route' => 'privacy'],
        ['slug' => 'cookies', 'title' => 'Cookies', 'route' => 'legal.cookies'],
        ['slug' => 'terms', 'title' => 'Terms of use', 'route' => 'legal.terms'],
        ['slug' => 'accessibility', 'title' => 'Accessibility statement', 'route' => 'legal.accessibility'],
    ] as $definition) {
        $page = LegalPageFixtures::page($definition['slug'], $definition['title']);
        LegalPageFixtures::publishVersion($page, '<h2>Overview</h2><p>Published '.$definition['slug'].' text.</p>', changeSummary: 'First publication.');

        $this->get(route($definition['route']))
            ->assertOk()
            ->assertSee($definition['title'])
            ->assertSee('Version 1')
            ->assertSee('Overview');
    }
});

it('redirects guests to login for signed-in-only legal pages', function () {
    $page = LegalPageFixtures::page('hosting-and-security', 'Hosting and security', LegalPageVisibility::SignedIn);
    LegalPageFixtures::publishVersion($page, '<h2>Hosting</h2><p>Members only.</p>');

    $this->get(route('legal.hosting-and-security'))
        ->assertRedirect(route('login'));
});

it('keeps the cookie register visible when the published wording is not yet in effect', function () {
    $page = LegalPageFixtures::page('cookies', 'Cookies');
    LegalPageFixtures::publishVersion($page, '<h2>Later</h2><p>Not yet.</p>', Carbon::parse('2030-01-01 09:00'));

    $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertSee('Cookie register')
        ->assertDontSee('Not yet.');

    $this->travelTo(Carbon::parse('2030-01-01 09:00'));

    $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertSee('Later')
        ->assertSee('Cookie register');
});

it('cannot update a published legal page version', function () {
    $page = LegalPageFixtures::page('terms', 'Terms of use');
    $version = LegalPageFixtures::publishVersion($page, '<p>Final</p>');
    $legalPages = app(LegalPages::class);

    expect(fn () => $legalPages->updateDraft($version, '<p>Hacked</p>'))
        ->toThrow(PublishedLegalVersionException::class);
});

it('strips scripts when legal page HTML is stored', function () {
    $sanitizer = app(HtmlSanitizer::class);
    $dirty = '<p onclick="alert(1)">Hi<script>x</script></p>';

    expect($sanitizer->clean($dirty))->not->toContain('onclick')
        ->and($sanitizer->clean($dirty))->not->toContain('<script');

    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    $version = app(LegalPages::class)->createDraft($page, $dirty);

    expect($version->body)->not->toContain('<script');
});

it('serves the privacy notice on /privacy', function () {
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($page, '<h2>Who we are</h2><p>APES CIC runs the intranet.</p><p>audit log entries are kept for a while.</p>');

    $this->get('/privacy')
        ->assertOk()
        ->assertSee('Privacy notice')
        ->assertSee('Who we are')
        ->assertSee('audit log');
});

it('lists published versions for a legal page', function () {
    $page = LegalPageFixtures::page('privacy', 'Privacy notice');
    LegalPageFixtures::publishVersion($page, '<h2>v1</h2><p>First.</p>', changeSummary: 'Initial wording.');
    app(LegalPages::class)->createDraft($page, '<h2>v2 draft</h2><p>Hidden.</p>');

    $this->get(route('legal.versions', $page->slug))
        ->assertOk()
        ->assertSee('Version 1')
        ->assertSee('Initial wording.')
        ->assertDontSee('Hidden.');
});
