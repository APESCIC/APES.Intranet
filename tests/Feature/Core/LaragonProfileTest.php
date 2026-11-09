<?php

function laragonProfile(): array
{
    return json_decode(file_get_contents(base_path('docs/laragon/profile.json')), true, flags: JSON_THROW_ON_ERROR);
}

function laragonEnvExample(): array
{
    $values = [];
    foreach (file(base_path('.env.laragon.example'), FILE_IGNORE_NEW_LINES) as $line) {
        if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $match) === 1) {
            $values[$match[1]] = trim($match[2], '"');
        }
    }

    return $values;
}

it('keeps the Laragon env example in step with the profile', function () {
    $profile = laragonProfile();
    $env = laragonEnvExample();

    expect($env['APP_ENV'])->toBe('local')
        ->and($env['APP_URL'])->toBe($profile['appUrl'])
        ->and($env['APP_URL'])->toBe('http://'.$profile['hostname'])
        ->and($env['DB_CONNECTION'])->toBe($profile['database']['engine'])
        ->and($env['DB_HOST'])->toBe($profile['database']['host'])
        ->and((int) $env['DB_PORT'])->toBe($profile['database']['port'])
        ->and($env['DB_DATABASE'])->toBe($profile['database']['name'])
        ->and($env['DB_USERNAME'])->toBe($profile['database']['username'])
        ->and($env['DB_PASSWORD'])->toBe('');
});

it('names the Laragon folder after the hostname', function () {
    $profile = laragonProfile();

    expect($profile['hostname'])->toBe($profile['project'].'.test')
        ->and($profile['folder'])->toBe('C:\\laragon\\www\\'.$profile['project']);
});

it('keeps the Vite port in step with the profile', function () {
    $vite = file_get_contents(base_path('vite.config.js'));

    expect($vite)->toMatch('/\bport:\s*'.laragonProfile()['viteDevServerPort'].',/')
        ->and($vite)->toContain('strictPort: true');
});

it('documents the same values in the readable profile', function () {
    $profile = laragonProfile();
    $doc = file_get_contents(base_path('docs/laragon/profile.md'));

    foreach ([$profile['hostname'], $profile['database']['name'], 'Port '.$profile['viteDevServerPort'], 'PHP | '.$profile['php'], 'Node | '.$profile['node']] as $value) {
        expect($doc)->toContain($value);
    }
});
