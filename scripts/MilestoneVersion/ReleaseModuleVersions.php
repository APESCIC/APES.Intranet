<?php

namespace Scripts\MilestoneVersion;

/**
 * Collects core and module SemVer values for a new release note (no Laravel bootstrap).
 */
final class ReleaseModuleVersions
{
    /**
     * @return array<string, string> core => vX.Y.Z tag; module slug => SemVer
     */
    public static function collect(string $root, SemVer $version): array
    {
        $versions = ['core' => $version->tag()];
        $modulesDir = $root.'/modules';
        if (! is_dir($modulesDir)) {
            return $versions;
        }

        foreach (glob($modulesDir.'/*/module.php') ?: [] as $path) {
            $slug = basename(dirname($path));
            $raw = require $path;
            if (! is_array($raw)) {
                continue;
            }
            $moduleVersion = $raw['version'] ?? null;
            if (is_string($moduleVersion) && $moduleVersion !== '') {
                $versions[$slug] = $moduleVersion;
            }
        }

        $core = $versions['core'];
        unset($versions['core']);
        ksort($versions, SORT_STRING);

        return ['core' => $core] + $versions;
    }
}
