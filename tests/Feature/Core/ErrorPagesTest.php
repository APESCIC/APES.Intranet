<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.debug' => false]);
});

it('shows the APES page for each error code without signing in', function (int $code, string $title) {
    Route::get('/_test/error', fn () => abort($code));

    $this->get('/_test/error')
        ->assertStatus($code)
        ->assertSee($title)
        ->assertSee("Error {$code}")
        ->assertSee('Skip to main content');
})->with([
    [403, 'You do not have access to this page'],
    [404, 'We cannot find that page'],
    [419, 'This page has expired'],
    [500, 'Something went wrong'],
    [503, 'The intranet is briefly unavailable'],
]);

it('uses the APES 404 page for unknown URLs', function () {
    $this->get('/no-such-page')->assertNotFound()->assertSee('We cannot find that page');
});

it('uses the APES 403 page when a role lacks access', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $staff = User::factory()->create()->assignRole(RoleName::Staff->value);

    $this->actingAs($staff)->get(route('admin.roles.index'))
        ->assertForbidden()
        ->assertSee('You do not have access to this page');
});

it('never shows a stack trace or exception message in production', function () {
    Route::get('/_test/crash', fn () => throw new RuntimeException('secret-db-password in /var/www/app/Thing.php'));

    $response = $this->get('/_test/crash')->assertStatus(500)->assertSee('Something went wrong');

    expect($response->getContent())
        ->not->toContain('secret-db-password')
        ->not->toContain('Thing.php')
        ->not->toContain('RuntimeException')
        ->not->toContain('Stack trace');
});

it('shows the 503 page in maintenance mode', function () {
    $this->artisan('down')->assertSuccessful();

    try {
        $this->get(route('login'))->assertStatus(503)->assertSee('We are updating or fixing the intranet');
    } finally {
        $this->artisan('up');
    }
});

it('shows a plain reason on the 503 page when sign-in is unavailable', function () {
    fakeCloudron($this);
    $this->idp->unavailable = true;

    $this->get(route('auth.redirect'))->assertStatus(503)->assertSee('Cloudron sign-in is unavailable');
});

it('renders error pages even when the database is down', function () {
    $default = config('database.default');
    config([
        'database.connections.broken' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x', 'password' => 'x'],
        'database.default' => 'broken',
        'session.driver' => 'array',
    ]);
    Route::get('/_test/db', fn () => DB::select('select 1'));

    try {
        $this->get('/_test/db')->assertStatus(500)->assertSee('Something went wrong');
    } finally {
        config(['database.default' => $default]);
    }
});
