<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, LegalPagesSeeder::class]);
});

function groupedFooterHtml(string $html): string
{
    return (string) str($html)->after('<footer class="site-footer">')->before('</footer>');
}

it('hides signed-in footer links for guests', function () {
    $html = groupedFooterHtml($this->get(route('login'))->getContent());

    expect($html)
        ->toContain('Privacy notice')
        ->not->toContain('Hosting and security');
});

it('shows signed-in footer links when authenticated', function () {
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $html = groupedFooterHtml($this->actingAs($user)->get(route('dashboard'))->getContent());

    expect($html)->toContain('Hosting and security');
});

it('shows the grouped footer on the sign-in page', function () {
    $html = groupedFooterHtml($this->get(route('login'))->getContent());

    expect($html)
        ->toContain('site-footer__groups')
        ->toContain('data-app-version');
});

it('shows the grouped footer on error pages', function () {
    $html = groupedFooterHtml($this->get('/no-such-page')->assertNotFound()->getContent());

    expect($html)
        ->toContain('site-footer__groups')
        ->toContain('Association of Protecting Exotic Species CIC');
});

it('renders a heading for each visible footer group', function () {
    $html = groupedFooterHtml($this->get(route('login'))->getContent());

    expect($html)
        ->toContain('id="site-footer-heading-legal"')
        ->toContain('site-footer__group-heading')
        ->toContain('Privacy notice')
        ->not->toContain('id="site-footer-heading-about"');
});

it('uses nav landmarks with headings for footer groups', function () {
    $html = groupedFooterHtml($this->get(route('login'))->getContent());

    expect($html)
        ->toContain('<nav class="site-footer__group" aria-labelledby="site-footer-heading-legal">')
        ->toContain('<h2 id="site-footer-heading-legal" class="site-footer__group-heading">')
        ->toContain('<ul class="site-footer__group-links">');
});

it('keeps footer link touch targets at 44px minimum', function () {
    $css = (string) file_get_contents(resource_path('css/shell.css'));
    $rule = (string) str($css)->after('.site-footer__group-link a {')->before('}');

    expect($rule)->toContain('min-height: 2.75rem');
});
