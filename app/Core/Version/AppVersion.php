<?php

namespace App\Core\Version;

/**
 * The version that is actually running, read from the VERSION file that
 * build-release.ps1 stamps into the release bundle. Never the newest GitHub
 * tag: a release can be tagged long before it is deployed.
 */
class AppVersion
{
    public const DEV = 'dev';

    public const PATTERN = '/^v\d+\.\d+\.\d+$/';

    public function __construct(private readonly ?string $path = null) {}

    /**
     * The deployed version, such as "v0.3.11", or "dev" when no valid VERSION
     * file is bundled (local development, CI, or a hand-built release).
     */
    public function current(): string
    {
        $path = $this->path ?? base_path('VERSION');
        if (! is_file($path)) {
            return self::DEV;
        }

        $version = trim((string) @file_get_contents($path));

        return self::isValid($version) ? $version : self::DEV;
    }

    public function isRelease(): bool
    {
        return $this->current() !== self::DEV;
    }

    public static function isValid(string $version): bool
    {
        return preg_match(self::PATTERN, $version) === 1;
    }
}
