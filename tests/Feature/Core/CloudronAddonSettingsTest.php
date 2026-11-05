<?php

use App\Core\System\CloudronAddons;

/**
 * Sets environment variables for one config file read, then removes them.
 *
 * @param  array<string, string>  $vars
 * @return array<string, mixed>
 */
function configWithEnv(string $file, array $vars): array
{
    $saved = [];
    foreach ($vars as $key => $value) {
        $saved[$key] = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    try {
        return require config_path($file.'.php');
    } finally {
        foreach ($saved as $key => [$env, $envArray, $server]) {
            $env === false ? putenv($key) : putenv("{$key}={$env}");
            if ($envArray === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $envArray;
            }
            if ($server === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $server;
            }
        }
    }
}

it('keeps addon passwords out of .env.example', function () {
    expect(CloudronAddons::forbiddenKeysIn(file_get_contents(base_path('.env.example'))))->toBe([]);
});

it('fails an env file that copies an addon password', function (string $line, array $expected) {
    expect(CloudronAddons::forbiddenKeysIn("APP_NAME=x\n{$line}\nAPP_ENV=production\n"))->toBe($expected);
})->with([
    'blank' => ['DB_PASSWORD=', []],
    'null' => ['MAIL_PASSWORD=null', []],
    'empty quotes' => ['REDIS_PASSWORD=""', []],
    'database' => ['DB_PASSWORD=copied', ['DB_PASSWORD']],
    'redis' => ['REDIS_PASSWORD=copied', ['REDIS_PASSWORD']],
    'mail' => ['MAIL_PASSWORD=copied', ['MAIL_PASSWORD']],
    'ldap' => ['LDAP_BIND_PASSWORD=copied', ['LDAP_BIND_PASSWORD']],
]);

it('has the preflight fail when shared/.env copies an addon password', function () {
    $preflight = file_get_contents(base_path('deploy/cloudron/preflight.sh'));

    foreach (CloudronAddons::FORBIDDEN_ENV_KEYS as $key) {
        expect($preflight)->toContain($key);
    }
    expect($preflight)->toContain('shared/.env holds no addon passwords');
});

it('reads the live MySQL addon values before anything in .env', function () {
    $config = configWithEnv('database', [
        'CLOUDRON_MYSQL_HOST' => 'mysql.addon', 'DB_HOST' => 'stale-host',
        'CLOUDRON_MYSQL_PASSWORD' => 'rotated', 'DB_PASSWORD' => 'stale',
        'CLOUDRON_MYSQL_DATABASE' => 'addon_db', 'DB_DATABASE' => 'stale_db',
        'DB_CONNECTION' => 'sqlite',
    ]);

    expect($config['default'])->toBe('mysql')
        ->and($config['connections']['mysql']['host'])->toBe('mysql.addon')
        ->and($config['connections']['mysql']['password'])->toBe('rotated')
        ->and($config['connections']['mysql']['database'])->toBe('addon_db');
});

it('reads the live Redis addon values and uses Redis for cache, sessions and queues', function () {
    $env = ['CLOUDRON_REDIS_HOST' => 'redis.addon', 'REDIS_HOST' => 'stale', 'CLOUDRON_REDIS_PASSWORD' => 'rotated', 'REDIS_PASSWORD' => 'stale'];

    $database = configWithEnv('database', $env);
    expect($database['redis']['default']['host'])->toBe('redis.addon')
        ->and($database['redis']['default']['password'])->toBe('rotated')
        ->and($database['redis']['cache']['password'])->toBe('rotated')
        ->and(configWithEnv('cache', $env)['default'])->toBe('redis')
        ->and(configWithEnv('session', $env)['driver'])->toBe('redis')
        ->and(configWithEnv('queue', $env)['default'])->toBe('redis');
});

it('falls back to the database for cache, sessions and queues without Redis', function () {
    $env = ['CACHE_STORE' => '', 'SESSION_DRIVER' => '', 'QUEUE_CONNECTION' => ''];

    expect(configWithEnv('cache', $env)['default'])->toBe('database')
        ->and(configWithEnv('session', $env)['driver'])->toBe('database')
        ->and(configWithEnv('queue', $env)['default'])->toBe('database');
});

it('reads the live sendmail and LDAP addon values before anything in .env', function () {
    $mail = configWithEnv('mail', [
        'CLOUDRON_MAIL_SMTP_SERVER' => 'mail.addon', 'MAIL_HOST' => 'stale',
        'CLOUDRON_MAIL_SMTP_PASSWORD' => 'rotated', 'MAIL_PASSWORD' => 'stale', 'MAIL_MAILER' => 'log',
    ]);
    $intranet = configWithEnv('intranet', [
        'CLOUDRON_LDAP_HOST' => 'ldap.addon', 'LDAP_HOST' => 'stale',
        'CLOUDRON_LDAP_BIND_PASSWORD' => 'rotated', 'LDAP_BIND_PASSWORD' => 'stale',
    ]);

    expect($mail['default'])->toBe('smtp')
        ->and($mail['mailers']['smtp']['host'])->toBe('mail.addon')
        ->and($mail['mailers']['smtp']['password'])->toBe('rotated')
        ->and($intranet['ldap']['host'])->toBe('ldap.addon')
        ->and($intranet['ldap']['bind_password'])->toBe('rotated');
});

it('documents every addon variable it reads', function () {
    $docs = file_get_contents(base_path('docs/deploy/cloudron.md'));

    foreach (CloudronAddons::definitions() as $addon) {
        foreach ($addon['variables'] as $variable) {
            expect($docs)->toContain($variable);
        }
    }
});
