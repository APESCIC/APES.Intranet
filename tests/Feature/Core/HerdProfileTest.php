<?php

function herdProfile(): array
{
    return json_decode(file_get_contents(base_path('docs/herd/profile.json')), true, flags: JSON_THROW_ON_ERROR);
}

function herdEnvExample(): array
{
    $values = [];
    foreach (file(base_path('.env.herd.example'), FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $match) === 1) {
            $values[$match[1]] = trim($match[2], '"');
        }
    }

    return $values;
}

it('keeps the Herd env example in step with the profile', function () {
    $profile = herdProfile();
    $env = herdEnvExample();

    expect($env['APP_ENV'])->toBe('local')
        ->and($env['APP_URL'])->toBe($profile['appUrl'])
        ->and($env['APP_URL'])->toBe('http://'.$profile['hostname'])
        ->and($env['DB_CONNECTION'])->toBe($profile['database']['engine'])
        ->and($env['DB_HOST'])->toBe($profile['database']['host'])
        ->and((int) $env['DB_PORT'])->toBe($profile['database']['port'])
        ->and($env['DB_DATABASE'])->toBe($profile['database']['name'])
        ->and($env['DB_USERNAME'])->toBe($profile['database']['username'])
        ->and($env['DB_PASSWORD'] ?? '')->toBe('')
        ->and($env['MAIL_MAILER'])->toBe($profile['mail']['mailer'])
        ->and($env['MAIL_HOST'])->toBe($profile['mail']['host'])
        ->and((int) $env['MAIL_PORT'])->toBe($profile['mail']['port']);
});

it('names the Herd site after the hostname', function () {
    $profile = herdProfile();

    expect($profile['hostname'])->toBe($profile['herdSite'].'.test')
        ->and($profile['herdSite'])->toBe($profile['project']);
});

it('keeps the Vite port in step with the profile', function () {
    $vite = file_get_contents(base_path('vite.config.js'));

    expect($vite)->toMatch('/\bport:\s*'.herdProfile()['viteDevServerPort'].',/')
        ->and($vite)->toContain('strictPort: true');
});

it('pins the site to the PHP version Cloudron runs', function () {
    $profile = herdProfile();

    expect($profile['php'])->toBe('8.4')
        ->and($profile['phpIsolated'])->toBeTrue()
        ->and(file_get_contents(base_path('docs/herd/profile.md')))->toContain('herd isolate '.$profile['php']);
});

it('keeps public/.htaccess safe for Cloudron', function () {
    $depth = 0;
    $outside = [];
    foreach (file(base_path('public/.htaccess'), FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^\s*<IfModule\s+(php_module|mod_php\d*\.c)\s*>/i', $line) === 1) {
            $depth++;
        } elseif ($depth > 0 && preg_match('#^\s*</IfModule>#i', $line) === 1) {
            $depth--;
        } elseif ($depth === 0 && preg_match('/^\s*php_(value|flag|admin_value|admin_flag)\b/i', $line) === 1) {
            $outside[] = trim($line);
        }
    }

    expect($outside)->toBe([])
        ->and(file_get_contents(base_path('public/.htaccess')))->toContain('HTTP_AUTHORIZATION');
});

it('documents the same values in the readable profile', function () {
    $profile = herdProfile();
    $doc = file_get_contents(base_path('docs/herd/profile.md'));

    foreach ([$profile['hostname'], $profile['database']['name'], 'Port '.$profile['viteDevServerPort'], 'port '.$profile['mail']['port'], 'PHP | '.$profile['php'], 'Node | '.$profile['node']] as $value) {
        expect($doc)->toContain($value);
    }
});
