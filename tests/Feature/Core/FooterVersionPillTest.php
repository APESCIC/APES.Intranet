<?php

use App\Core\Access\RoleName;
use App\Core\Changelog\Release;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function pillRelease(string $version, array $audiences = ['staff', 'volunteers', 'students']): Release
{
    [$major, $minor, $patch] = array_map('intval', explode('.', ltrim($version, 'v')));

    return Release::query()->create([
        'version' => $version, 'major' => $major, 'minor' => $minor, 'patch' => $patch,
        'title' => "Release {$version}", 'released_at' => '2026-11-09', 'summary' => 'Summary.',
        'audiences' => $audiences, 'changes_by_type' => ['added' => ['Thing.']], 'is_published' => true,
    ]);
}

function pillUser(RoleName $role): User
{
    return User::factory()->create()->assignRole($role->value);
}

function footerOf(string $html): string
{
    return (string) str($html)->after('<footer class="site-footer">')->before('</footer>');
}

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

afterEach(fn () => @unlink(base_path('VERSION')));

it('shows the deployed version, not the newest release, and links to its entry', function () {
    pillRelease('v0.3.10');
    pillRelease('v0.3.11');
    file_put_contents(base_path('VERSION'), 'v0.3.10');

    $footer = footerOf($this->actingAs(pillUser(RoleName::Staff))->get(route('dashboard'))->getContent());

    expect($footer)->toContain('</span>v0.3.10</a>')
        ->and($footer)->not->toContain('v0.3.11')
        ->and($footer)->toContain('href="'.route('change-log.show', 'v0.3.10').'"')
        ->and(trim(strip_tags((string) str($footer)->after('data-app-version>')->before('</a>'))))
        ->toBe('View the intranet change log for version v0.3.10');
});

it('links to the hub when the viewer may not read the deployed entry', function () {
    pillRelease('v0.3.10', ['staff']);
    file_put_contents(base_path('VERSION'), 'v0.3.10');

    $footer = footerOf($this->actingAs(pillUser(RoleName::Student))->get(route('dashboard'))->getContent());

    expect($footer)->toContain('>v0.3.10</a>')
        ->and($footer)->toContain('href="'.route('change-log.index').'"');
});

it('says dev and links to the hub when no version is stamped', function () {
    $footer = footerOf($this->actingAs(pillUser(RoleName::Staff))->get(route('dashboard'))->getContent());

    expect($footer)->toContain('</span>dev<span class="sr-only"> version</span></a>')
        ->and($footer)->toContain('href="'.route('change-log.index').'"');
});

it('shows the pill in Admin menu mode too', function () {
    file_put_contents(base_path('VERSION'), 'v0.3.11');
    $admin = User::factory()->create();
    $admin->assignRole(RoleName::Staff->value);
    $admin->assignRole(RoleName::Admin->value);

    $footer = footerOf($this->actingAs($admin)->get(route('admin.roles.index'))->getContent());

    expect($footer)->toContain('data-app-version')->and($footer)->toContain('>v0.3.11</a>');
});

it('never shows the pill to guests or on error pages', function () {
    file_put_contents(base_path('VERSION'), 'v0.3.11');

    expect($this->get(route('login'))->getContent())->not->toContain('data-app-version')
        ->and($this->get('/no-such-page')->assertNotFound()->getContent())->not->toContain('data-app-version');
});

it('keeps the pill a 44px target that wraps on small screens', function () {
    $css = (string) file_get_contents(resource_path('css/shell.css'));
    $rule = (string) str($css)->after('.site-footer__version {')->before('}');

    expect($rule)->toContain('min-height: 2.75rem')
        ->and($rule)->toContain('min-width: 2.75rem')
        ->and((string) str($css)->after('.site-footer {')->before('}'))->toContain('flex-wrap: wrap');
});
