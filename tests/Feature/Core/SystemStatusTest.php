<?php

use App\Core\Access\RoleName;
use App\Core\Audit\AuditLog;
use App\Core\System\SystemCheck;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const SECRETS = ['redis-pass-S3CRET1', 'mail-pass-S3CRET2', 'oidc-S3CRET3', 'db-pass-S3CRET4', 'cloudron-api-S3CRET5'];

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    // Every addon points somewhere unreachable, with a known secret.
    config([
        'database.redis.default' => array_merge(config('database.redis.default'), ['host' => '127.0.0.1', 'port' => 1, 'password' => SECRETS[0], 'max_retries' => 0, 'timeout' => 1]),
        'mail.default' => 'smtp',
        'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp'), ['host' => '127.0.0.1', 'port' => 1, 'password' => SECRETS[1]]),
        'intranet.oidc' => array_merge(config('intranet.oidc'), ['issuer' => 'https://cloudron.example.test/openid', 'client_id' => 'intranet', 'client_secret' => SECRETS[2]]),
        'intranet.cloudron' => array_merge(config('intranet.cloudron'), ['api_base' => 'https://my.cloudron.example.test', 'api_token' => SECRETS[4]]),
        'database.connections.mysql.password' => SECRETS[3],
        'intranet.cloudron_variables' => ['CLOUDRON_MYSQL_HOST', 'CLOUDRON_MYSQL_PASSWORD', 'CLOUDRON_REDIS_HOST', 'CLOUDRON_REDIS_PASSWORD', 'CLOUDRON_API_TOKEN'],
    ]);
    Http::fake([
        'https://cloudron.example.test/.well-known/openid-configuration' => Http::response([
            'issuer' => 'https://cloudron.example.test/openid',
            'scopes_supported' => ['openid', 'profile', 'email', 'groups'],
            'claims_supported' => ['sub', 'email', 'name', 'groups'],
        ], 200),
        'https://my.cloudron.example.test/api/v1/users*' => Http::response(['users' => []], 200),
        '*' => Http::response('down', 500),
    ]);
});

function freshAdmin(object $test, RoleName $role = RoleName::Admin): User
{
    $user = User::factory()->create()->assignRole($role->value);
    $test->actingAs($user)->withSession(['intranet.auth_time' => now()->getTimestamp()]);

    return $user;
}

it('is for admins only', function (RoleName $role) {
    freshAdmin($this, $role);

    $this->get(route('admin.system.index'))->assertForbidden();
})->with(array_values(array_filter(RoleName::cases(), fn (RoleName $role) => $role !== RoleName::Admin)));

it('sends guests to sign in', function () {
    $this->get(route('admin.system.index'))->assertRedirect(route('login'));
});

it('sends an admin with a stale sign-in to Cloudron to sign in again', function () {
    $admin = User::factory()->create()->assignRole(RoleName::Admin->value);
    $this->actingAs($admin)->withSession(['intranet.auth_time' => now()->subHour()->getTimestamp()]);

    $this->get(route('admin.system.index'))->assertRedirect(route('auth.reauth'));
});

it('shows each addon with its presence, health and masked variables', function () {
    freshAdmin($this);

    $response = $this->get(route('admin.system.index'))->assertOk();

    foreach (['MySQL', 'Redis', 'Sendmail', 'OIDC client (groups claim)', 'Cloudron API (directory sync)', 'Scheduler'] as $label) {
        $response->assertSee($label);
    }
    $response->assertDontSee('data-addon="ldap"', false);
    $response->assertSee('CLOUDRON_API_TOKEN')
        ->assertSee('•••••• (set)');
    $response->assertSee('data-summary="checks"', false)
        ->assertSee('data-summary="version"', false)
        ->assertSee('data-summary="backup"', false)
        ->assertSee('data-summary="queue"', false)
        ->assertSee('Details and Cloudron variables')
        ->assertSee('CLOUDRON_REDIS_PASSWORD')
        ->assertSee('•••••• (set)')
        ->assertDontSee('CLOUDRON_LDAP_BIND_PASSWORD');
    expect(SystemCheck::where('check', 'mysql')->value('status'))->toBe(SystemCheck::OK);
});

it('keeps Cloudron variable names inside a closed details block by default', function () {
    freshAdmin($this);

    $html = $this->get(route('admin.system.index'))->assertOk()->getContent();
    preg_match('/data-addon="redis"[^>]*>([\s\S]*?)data-addon="/', $html, $match);
    $redis = $match[1] ?? '';

    expect($redis)->toContain('<details')
        ->and($redis)->not->toContain('<details open')
        ->and($redis)->toContain('CLOUDRON_REDIS_PASSWORD')
        ->and($redis)->toContain('sg-badge');
});

it('shows a stopped Redis as down', function () {
    freshAdmin($this);

    $html = $this->get(route('admin.system.index'))->assertOk()->getContent();

    expect($html)->toMatch('/data-addon="redis"[^>]*>[\s\S]*?data-status="down"/')
        ->and(SystemCheck::where('check', 'redis')->value('status'))->toBe(SystemCheck::DOWN);
});

it('never shows or logs a password, secret or token', function () {
    $logged = [];
    Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged) {
        $logged[] = $event->message.' '.json_encode($event->context);
    });
    freshAdmin($this);

    $html = $this->get(route('admin.system.index'))->assertOk()->getContent();

    foreach (SECRETS as $secret) {
        expect($html)->not->toContain($secret)
            ->and(implode("\n", $logged))->not->toContain($secret);
    }
    expect($html)->not->toMatch('/_(PASSWORD|SECRET)\s*=/');
});

it('records an audit entry for each view', function () {
    $admin = freshAdmin($this);

    $this->get(route('admin.system.index'));
    $this->get(route('admin.system.index'));

    expect(AuditLog::where('event', 'admin.system.viewed')->where('actor_id', $admin->id)->count())->toBe(2);
});

it('shows the scheduler as not run, then down when its heartbeat is stale', function () {
    freshAdmin($this);

    $scheduler = fn () => (string) str($this->get(route('admin.system.index'))->getContent())->after('data-addon="scheduler"')->before('</section>');
    expect($scheduler())->toContain('data-status="not_run"');

    SystemCheck::record('scheduler', SystemCheck::OK);
    expect($scheduler())->toContain('data-status="ok"');

    $this->travel(16)->minutes();
    $this->withSession(['intranet.auth_time' => now()->getTimestamp()]);
    expect($scheduler())->toContain('data-status="down"');
});
