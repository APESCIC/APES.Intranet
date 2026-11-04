<?php

use App\Core\Access\RoleName;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function manifest(): array
{
    return json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
}

it('has an installable web manifest that opens at sign-in or the dashboard', function () {
    $manifest = manifest();

    expect($manifest)->toMatchArray([
        'name' => 'APES Intranet',
        'short_name' => 'Intranet',
        'start_url' => '/',
        'scope' => '/',
        'display' => 'standalone',
        'theme_color' => '#d2f4fa',
    ]);

    $sizes = collect($manifest['icons'])->pluck('sizes')->all();
    expect($sizes)->toContain('192x192', '512x512')
        ->and(collect($manifest['icons'])->pluck('purpose')->all())->toContain('maskable');
});

it('ships every icon the manifest lists, at the stated size', function () {
    foreach (manifest()['icons'] as $icon) {
        $path = public_path(ltrim($icon['src'], '/'));
        expect($path)->toBeFile();

        [$width, $height] = getimagesize($path);
        expect("{$width}x{$height}")->toBe($icon['sizes']);
    }
});

it('links the manifest, theme colour and touch icon from every page', function () {
    $this->get(route('login'))
        ->assertSee('rel="manifest"', false)
        ->assertSee('name="theme-color" content="#d2f4fa"', false)
        ->assertSee('rel="apple-touch-icon"', false);
});

it('has no service worker or offline cache', function () {
    expect(public_path('sw.js'))->not->toBeFile()
        ->and(public_path('service-worker.js'))->not->toBeFile()
        ->and(file_get_contents(resource_path('js/app.js')))->not->toContain('serviceWorker');
});

it('tells browsers not to store pages for signed-in people', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create()->assignRole(RoleName::Staff->value);

    $cacheControl = $this->actingAs($user)->get(route('dashboard'))->assertOk()->headers->get('Cache-Control');

    expect($cacheControl)->toContain('no-store')->toContain('private');
});
