<?php

use App\Core\Access\RoleName;
use App\Core\Legal\CookieRegister;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Modules\Operations\Models\Form;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Support\Operations;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, LegalPagesSeeder::class]);
});

/**
 * @return list<string>
 */
function setCookieNamesFrom(TestResponse $response): array
{
    return array_map(
        fn (Cookie $cookie) => $cookie->getName(),
        $response->headers->getCookies(),
    );
}

function publishedForm(): Form
{
    $form = Form::query()->create([
        'title' => 'Visitor feedback',
        'status' => Form::STATUS_PUBLISHED,
        'published_at' => now(),
        'route_to_role' => RoleName::Admin->value,
    ]);
    $form->fields()->create([
        'label' => 'Comments',
        'type' => 'textarea',
        'required' => true,
        'sort_order' => 10,
    ]);

    return $form;
}

it('lists every response cookie in the legal register', function () {
    Operations::seedRoles();
    $register = app(CookieRegister::class)->registeredNames();
    $staff = Operations::member();
    $form = publishedForm();

    $paths = [
        route('login'),
        route('dashboard'),
        route('operations.forms.show', $form),
    ];

    foreach ($paths as $path) {
        $response = str_contains($path, 'dashboard') || str_contains($path, 'forms')
            ? $this->actingAs($staff)->get($path)
            : $this->get($path);

        $response->assertOk();

        foreach (setCookieNamesFrom($response) as $name) {
            expect(in_array($name, $register, true))
                ->toBeTrue("Cookie [{$name}] from [{$path}] is not in config/legal.php");
        }
    }
});

it('shows the cookie register on /cookies with names and durations from config', function () {
    $register = app(CookieRegister::class)->entries();
    $sessionName = config('session.cookie');

    $response = $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertSee('Cookie register')
        ->assertSee('XSRF-TOKEN')
        ->assertSee($sessionName);

    foreach ($register as $entry) {
        $response->assertSee($entry['duration']);
    }
});

it('does not render a cookie consent banner or dialog', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('cookie banner', false)
        ->assertDontSee('consent dialog', false);

    $this->get(route('legal.cookies'))
        ->assertOk()
        ->assertDontSee('consent dialog', false);
});
