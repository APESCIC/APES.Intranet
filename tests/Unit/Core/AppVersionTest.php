<?php

use App\Core\Version\AppVersion;

function versionFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'apes-version-');
    file_put_contents($path, $contents);

    return $path;
}

function versionFixtureRoot(callable $build): string
{
    $root = sys_get_temp_dir().'/apes-appversion-'.uniqid();
    mkdir($root);
    mkdir($root.'/changelog');
    mkdir($root.'/changelog/releases');
    $build($root);

    return $root;
}

function writePublishedRelease(string $root, string $version): void
{
    file_put_contents($root.'/changelog/releases/'.$version.'.json', json_encode([
        'version' => $version,
        'released_at' => '2026-11-22',
        'title' => 'Title',
        'summary' => 'Summary.',
        'audiences' => ['staff'],
        'changes' => ['added' => ['Thing.']],
        'published' => true,
    ], JSON_PRETTY_PRINT));
}

it('reads the stamped version', function () {
    $path = versionFile("v0.3.11\n");

    try {
        $version = new AppVersion($path);

        expect($version->current())->toBe('v0.3.11')
            ->and($version->label())->toBe('v0.3.11')
            ->and($version->isRelease())->toBeTrue();
    } finally {
        unlink($path);
    }
});

it('keeps current and isRelease strict when VERSION is missing', function () {
    $root = versionFixtureRoot(function (string $root): void {
        writePublishedRelease($root, 'v0.6.13');
    });

    try {
        $version = new AppVersion($root.'/VERSION-missing', $root);

        expect($version->current())->toBe(AppVersion::DEV)
            ->and($version->isRelease())->toBeFalse()
            ->and($version->label())->toBe('v0.6.13+dev');
    } finally {
        array_map('unlink', glob($root.'/changelog/releases/*') ?: []);
        rmdir($root.'/changelog/releases');
        rmdir($root.'/changelog');
        rmdir($root);
    }
});

it('builds a development label from published JSON and a fixture .git', function () {
    $root = versionFixtureRoot(function (string $root): void {
        writePublishedRelease($root, 'v0.6.12');
        writePublishedRelease($root, 'v0.6.13');
        mkdir($root.'/.git');
        mkdir($root.'/.git/refs');
        mkdir($root.'/.git/refs/heads');
        file_put_contents($root.'/.git/HEAD', "ref: refs/heads/main\n");
        file_put_contents($root.'/.git/refs/heads/main', "0b263d7444e0abcdef0123456789abcdef012345\n");
    });

    try {
        $version = new AppVersion($root.'/no-VERSION', $root);

        expect($version->label())->toBe('v0.6.13+dev.0b263d7')
            ->and($version->baseVersion())->toBe('v0.6.13')
            ->and($version->isDevelopmentBuild())->toBeTrue()
            ->and($version->current())->toBe(AppVersion::DEV);
    } finally {
        @unlink($root.'/.git/refs/heads/main');
        @rmdir($root.'/.git/refs/heads');
        @rmdir($root.'/.git/refs');
        @unlink($root.'/.git/HEAD');
        @rmdir($root.'/.git');
        array_map('unlink', glob($root.'/changelog/releases/*') ?: []);
        rmdir($root.'/changelog/releases');
        rmdir($root.'/changelog');
        rmdir($root);
    }
});

it('falls back to plain dev when nothing is present', function () {
    $root = versionFixtureRoot(fn () => null);

    try {
        $version = new AppVersion($root.'/missing', $root);

        expect($version->label())->toBe(AppVersion::DEV)
            ->and($version->current())->toBe(AppVersion::DEV);
    } finally {
        rmdir($root.'/changelog/releases');
        rmdir($root.'/changelog');
        rmdir($root);
    }
});

it('falls back to dev when the VERSION file is malformed', function (string $contents) {
    $path = versionFile($contents);

    try {
        expect((new AppVersion($path))->current())->toBe(AppVersion::DEV);
    } finally {
        unlink($path);
    }
})->with([
    'empty' => '',
    'branch name' => 'main',
    'no v prefix' => '0.3.11',
    'two parts' => 'v0.3',
    'suffix' => 'v0.3.11-beta',
    'markup' => '<script>v0.3.11</script>',
]);

it('does not call a shell or Process from AppVersion', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3).'/app/Core/Version/AppVersion.php');

    expect($source)->not->toContain('shell_exec')
        ->and($source)->not->toContain('proc_open')
        ->and($source)->not->toContain('Process::')
        ->and($source)->not->toContain('exec(')
        ->and($source)->not->toContain('passthru')
        ->and($source)->not->toContain('system(');
});
