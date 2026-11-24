<?php

use App\Core\Legal\LegalPageVersionStatus;
use App\Models\LegalPage;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, LegalPagesSeeder::class]);
});

it('seeds headings-only drafts for cookies, terms and accessibility', function () {
    foreach (['cookies', 'terms', 'accessibility'] as $slug) {
        $page = LegalPage::query()->where('slug', $slug)->firstOrFail();

        expect($page->versions()->count())->toBe(1)
            ->and($page->versions()->where('status', LegalPageVersionStatus::Draft)->count())->toBe(1)
            ->and($page->versions()->where('status', LegalPageVersionStatus::Published)->count())->toBe(0);
    }
});

it('keeps privacy as a draft and publishes hosting from the seeder', function () {
    $privacy = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

    expect($privacy->versions()->where('status', LegalPageVersionStatus::Draft)->count())->toBe(1)
        ->and($privacy->versions()->where('status', LegalPageVersionStatus::Published)->count())->toBe(0);

    $hosting = LegalPage::query()->where('slug', 'hosting-and-security')->firstOrFail();

    expect($hosting->versions()->where('status', LegalPageVersionStatus::Published)->count())->toBe(1);
});

it('requires acceptance of terms of use', function () {
    $terms = LegalPage::query()->where('slug', 'terms')->firstOrFail();

    expect($terms->needs_acceptance)->toBeTrue();
});

it('shows the cookie register for guests when only a draft exists', function () {
    $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertSee('Cookie register')
        ->assertSee('XSRF-TOKEN');
});
