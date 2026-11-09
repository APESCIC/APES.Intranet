<?php

use App\Core\Changelog\ChangelogEntryValidator;

function releaseNote(array $overrides = []): array
{
    return array_merge([
        'version' => 'v1.2.3',
        'released_at' => '2026-11-09',
        'title' => 'A release',
        'summary' => 'What this release means for you.',
        'audiences' => ['staff', 'volunteers', 'students'],
        'changes' => ['added' => ['Something new.']],
        'published' => true,
    ], $overrides);
}

function releaseNotesFolder(array $files): string
{
    $directory = sys_get_temp_dir().'/apes-changelog-'.uniqid();
    mkdir($directory);
    foreach ($files as $name => $payload) {
        file_put_contents("{$directory}/{$name}", is_string($payload) ? $payload : json_encode($payload));
    }

    return $directory;
}

it('accepts a complete entry', function () {
    expect((new ChangelogEntryValidator)->validatePayload(releaseNote(), 'v1.2.3.json'))->toBe([]);
});

it('accepts optional internal notes', function () {
    expect((new ChangelogEntryValidator)->validatePayload(releaseNote(['internal_notes' => ['For admins.']])))->toBe([]);
});

it('rejects a bad schema', function (array $overrides, string $error) {
    $errors = (new ChangelogEntryValidator)->validatePayload(releaseNote($overrides));

    expect(implode("\n", $errors))->toContain($error);
})->with([
    'bad version' => [['version' => '1.2.3'], 'version must look like v1.2.3'],
    'bad date' => [['released_at' => '2026-02-30'], 'released_at must be a real date'],
    'empty title' => [['title' => ' '], 'title must be non-empty text'],
    'unknown audience' => [['audiences' => ['public']], 'audiences must list'],
    'repeated audience' => [['audiences' => ['staff', 'staff']], 'audiences must list'],
    'no audiences' => [['audiences' => []], 'audiences must list'],
    'unknown change type' => [['changes' => ['improved' => ['x']]], 'unknown change type "improved"'],
    'empty changes' => [['changes' => []], 'changes must be an object'],
    'empty change list' => [['changes' => ['fixed' => []]], 'changes.fixed must be a non-empty list'],
    'published not boolean' => [['published' => 'yes'], 'published must be true or false'],
    'internal notes not a list' => [['internal_notes' => 'x'], 'internal_notes must be a non-empty list'],
]);

it('rejects missing and unknown fields', function () {
    $payload = releaseNote(['pr' => 12]);
    unset($payload['summary']);

    expect((new ChangelogEntryValidator)->validatePayload($payload))
        ->toContain('missing "summary"')
        ->toContain('unknown field "pr"');
});

it('rejects a file name that does not match the version', function () {
    expect((new ChangelogEntryValidator)->validatePayload(releaseNote(), 'v1.2.4.json'))
        ->toContain('file name must be v1.2.3.json');
});

it('rejects secret-like and personal values anywhere in the entry', function (string $text) {
    $errors = (new ChangelogEntryValidator)->validatePayload(releaseNote([
        'internal_notes' => [$text],
    ]));

    expect(implode("\n", $errors))->toContain('release notes must not');
})->with([
    'password' => 'The password: hunter2 was rotated',
    'env setting' => 'Set APP_KEY=abc on the server',
    'encoded key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAA',
    'github token' => 'ghp_abcdefghijklmnopqrstuvwxyz0123',
    'private key' => '-----BEGIN RSA PRIVATE KEY-----',
    'commit hash' => 'Fixed in 8b8cd74cb06b826b38fdf07ca3f5975909cace8a',
    'email' => 'Ask jane.doe@example.org for access',
    'phone' => 'Call 07700 900123',
    'ip address' => 'The server at 10.0.0.12',
    'hostname' => 'Open my.cloudron.example.org',
    'sql' => 'Ran SELECT * FROM users',
]);

it('rejects a second file for a version that already has one', function () {
    $directory = releaseNotesFolder([
        'v1.2.3.json' => releaseNote(),
        'v1.2.3-copy.json' => releaseNote(),
    ]);

    expect((new ChangelogEntryValidator)->validateDirectory($directory))
        ->toBe(['v1.2.3-copy.json: file name must be v1.2.3.json']);
});

it('reports invalid JSON with the file name', function () {
    $directory = releaseNotesFolder(['v1.2.3.json' => '{"version": ']);

    expect((new ChangelogEntryValidator)->validateDirectory($directory)[0])->toStartWith('v1.2.3.json: invalid JSON');
});

it('skips template files that start with an underscore', function () {
    $directory = releaseNotesFolder(['_template.json' => '{}', 'v1.2.3.json' => releaseNote()]);

    expect((new ChangelogEntryValidator)->validateDirectory($directory))->toBe([]);
});

it('can require a published entry for a tag', function () {
    $directory = releaseNotesFolder([
        'v1.2.3.json' => releaseNote(),
        'v1.2.4.json' => releaseNote(['version' => 'v1.2.4', 'published' => false]),
    ]);
    $validator = new ChangelogEntryValidator;

    expect($validator->validateDirectory($directory, 'v1.2.3'))->toBe([])
        ->and($validator->validateDirectory($directory, 'v1.2.4')[0])->toContain('is not published')
        ->and($validator->validateDirectory($directory, 'v1.2.5')[0])->toContain('has no release note');
});

it('sorts entries by version, oldest first', function () {
    $directory = releaseNotesFolder([
        'v0.3.10.json' => releaseNote(['version' => 'v0.3.10']),
        'v0.3.9.json' => releaseNote(['version' => 'v0.3.9']),
        'v0.10.0.json' => releaseNote(['version' => 'v0.10.0']),
    ]);

    expect(array_map('basename', (new ChangelogEntryValidator)->entryPaths($directory)))
        ->toBe(['v0.3.9.json', 'v0.3.10.json', 'v0.10.0.json']);
});
