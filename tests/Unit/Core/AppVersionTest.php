<?php

use App\Core\Version\AppVersion;

function versionFile(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'apes-version-');
    file_put_contents($path, $contents);

    return $path;
}

it('reads the stamped version', function () {
    $path = versionFile("v0.3.11\n");

    try {
        $version = new AppVersion($path);

        expect($version->current())->toBe('v0.3.11')
            ->and($version->isRelease())->toBeTrue();
    } finally {
        unlink($path);
    }
});

it('falls back to dev when the VERSION file is missing', function () {
    $version = new AppVersion(sys_get_temp_dir().'/apes-version-missing-'.uniqid());

    expect($version->current())->toBe(AppVersion::DEV)
        ->and($version->isRelease())->toBeFalse();
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
