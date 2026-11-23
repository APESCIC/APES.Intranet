<?php

namespace App\Core\Version;

use App\Core\Health\LocalRevision;

/**
 * The version that is actually running. The stamped VERSION file (from the
 * release bundle) wins. When it is missing (Herd, CI), the display label uses
 * the highest published change-log JSON plus a short git commit from .git
 * files — never a shell call. Health checks keep the strict stamped value.
 */
class AppVersion
{
    public const DEV = 'dev';

    public const PATTERN = '/^v\d+\.\d+\.\d+$/';

    private ?string $currentCache = null;

    private ?string $labelCache = null;

    public function __construct(
        private readonly ?string $path = null,
        private readonly ?string $basePath = null,
    ) {}

    /**
     * The deployed version, such as "v0.3.11", or "dev" when no valid VERSION
     * file is bundled. Used by /healthz and isRelease().
     */
    public function current(): string
    {
        return $this->currentCache ??= $this->readStamped();
    }

    /**
     * Label for the footer pill and change-log hub. Prefer the stamped file;
     * otherwise the newest published release JSON with +dev[.shortsha].
     */
    public function label(): string
    {
        return $this->labelCache ??= $this->resolveLabel();
    }

    /**
     * The vX.Y.Z the display label is based on, when one exists.
     */
    public function baseVersion(): ?string
    {
        $label = $this->label();
        if (self::isValid($label)) {
            return $label;
        }
        if (preg_match('/^(v\d+\.\d+\.\d+)/', $label, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    public function isRelease(): bool
    {
        return $this->current() !== self::DEV;
    }

    public function isDevelopmentBuild(): bool
    {
        return ! $this->isRelease() && $this->label() !== self::DEV;
    }

    public static function isValid(string $version): bool
    {
        return preg_match(self::PATTERN, $version) === 1;
    }

    private function root(): string
    {
        return $this->basePath ?? base_path();
    }

    private function readStamped(): string
    {
        $path = $this->path ?? $this->root().DIRECTORY_SEPARATOR.'VERSION';
        if (! is_file($path)) {
            return self::DEV;
        }

        $version = trim((string) @file_get_contents($path));

        return self::isValid($version) ? $version : self::DEV;
    }

    private function resolveLabel(): string
    {
        $stamped = $this->current();
        if ($stamped !== self::DEV) {
            return $stamped;
        }

        $published = $this->highestPublishedRelease();
        if ($published === null) {
            return self::DEV;
        }

        $sha = (new LocalRevision($this->root()))->current();
        if ($sha === null || $sha === '') {
            return $published.'+dev';
        }

        return $published.'+dev.'.substr($sha, 0, 7);
    }

    private function highestPublishedRelease(): ?string
    {
        $dir = $this->root().DIRECTORY_SEPARATOR.'changelog'.DIRECTORY_SEPARATOR.'releases';
        if (! is_dir($dir)) {
            return null;
        }

        $best = null;
        $bestTuple = null;

        foreach (glob($dir.DIRECTORY_SEPARATOR.'v*.json') ?: [] as $file) {
            $raw = @file_get_contents($file);
            if ($raw === false) {
                continue;
            }
            $payload = json_decode($raw, true);
            if (! is_array($payload) || ($payload['published'] ?? null) !== true) {
                continue;
            }
            $version = $payload['version'] ?? null;
            if (! is_string($version) || ! self::isValid($version)) {
                continue;
            }
            $tuple = array_map('intval', explode('.', ltrim($version, 'v')));
            if ($bestTuple === null || $this->compareTuples($tuple, $bestTuple) > 0) {
                $bestTuple = $tuple;
                $best = $version;
            }
        }

        return $best;
    }

    /**
     * @param  list<int>  $a
     * @param  list<int>  $b
     */
    private function compareTuples(array $a, array $b): int
    {
        for ($i = 0; $i < 3; $i++) {
            $left = $a[$i] ?? 0;
            $right = $b[$i] ?? 0;
            if ($left !== $right) {
                return $left <=> $right;
            }
        }

        return 0;
    }
}
