<?php

use App\Core\Health\HealthCheck;
use Illuminate\Support\Facades\Redis;

function redisAnswers(bool $up): void
{
    $connection = Mockery::mock();
    $up
        ? $connection->shouldReceive('ping')->andReturn(true)
        : $connection->shouldReceive('ping')->andThrow(new RuntimeException('Connection refused to secret-host:6379'));

    Redis::shouldReceive('connection')->andReturn($connection);
}

it('returns 200 when the app, database and Redis are up', function () {
    redisAnswers(true);

    $this->getJson('/healthz')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => ['app' => 'ok', 'database' => 'ok', 'redis' => 'ok'],
            'version' => 'dev',
            'revision' => null,
        ]);
});

it('returns 503 when Redis is down, without leaking the error', function () {
    redisAnswers(false);

    $response = $this->getJson('/healthz')->assertStatus(503)
        ->assertJsonPath('status', 'fail')
        ->assertJsonPath('checks.redis', 'fail')
        ->assertJsonPath('checks.database', 'ok');

    expect($response->getContent())->not->toContain('secret-host')->not->toContain('refused');
});

it('returns 503 when the database is down', function () {
    redisAnswers(true);
    config([
        'database.connections.broken' => ['driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x', 'username' => 'x', 'password' => 'hunter2'],
        'database.default' => 'broken',
    ]);

    $response = $this->getJson('/healthz')->assertStatus(503)->assertJsonPath('checks.database', 'fail');

    expect($response->getContent())->not->toContain('hunter2');
});

it('never includes secrets or settings in the output', function () {
    redisAnswers(true);

    $body = $this->getJson('/healthz')->getContent();

    expect(json_decode($body, true))->toHaveKeys(['status', 'checks', 'version', 'revision'])->toHaveCount(4)
        ->and($body)->not->toContain((string) config('app.key'))
        ->and($body)->not->toContain((string) config('database.connections.mysql.host'));
});

it('sets no cookies and is not cached', function () {
    redisAnswers(true);

    $response = $this->get('/healthz');

    expect($response->headers->getCookies())->toBe([])
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('reports the deployed revision when a REVISION file exists', function () {
    redisAnswers(true);
    file_put_contents(base_path('REVISION'), "abc123def4567890\n");

    try {
        $this->getJson('/healthz')->assertJsonPath('revision', 'abc123def456');
    } finally {
        unlink(base_path('REVISION'));
    }
});

it('reports the deployed version and revision from the release bundle', function () {
    redisAnswers(true);
    file_put_contents(base_path('VERSION'), "v0.3.11\n");
    file_put_contents(base_path('REVISION'), "abc123def4567890\n");

    try {
        $this->getJson('/healthz')
            ->assertJsonPath('version', 'v0.3.11')
            ->assertJsonPath('revision', 'abc123def456');
    } finally {
        unlink(base_path('VERSION'));
        unlink(base_path('REVISION'));
    }
});

it('reports dev when the VERSION file is malformed', function () {
    redisAnswers(true);
    file_put_contents(base_path('VERSION'), "main\n");

    try {
        $this->getJson('/healthz')->assertOk()->assertJsonPath('version', 'dev');
    } finally {
        unlink(base_path('VERSION'));
    }
});

it('runs all three checks', function () {
    redisAnswers(true);

    expect(app(HealthCheck::class)->run())->toHaveKeys(['app', 'database', 'redis']);
});
