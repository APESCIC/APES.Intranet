<?php

namespace Tests\Support;

use App\Core\Modules\InvalidModuleSettingException;
use App\Core\Modules\ModuleManifest;
use Illuminate\Support\Str;

final class ModuleConventions
{
    /**
     * @return list<string>
     */
    public static function shippedSlugs(?string $modulesRoot = null): array
    {
        $modulesRoot ??= base_path('modules');
        $slugs = [];
        foreach (glob($modulesRoot.'/*/module.php') ?: [] as $manifestPath) {
            $slugs[] = basename(dirname($manifestPath));
        }

        sort($slugs);

        return $slugs;
    }

    /**
     * @return list<string>
     */
    public static function violations(string $slug, ?string $modulesRoot = null): array
    {
        $modulesRoot ??= base_path('modules');
        $moduleRoot = $modulesRoot.DIRECTORY_SEPARATOR.$slug;
        $manifestPath = $moduleRoot.DIRECTORY_SEPARATOR.'module.php';
        $violations = [];

        if (! is_file($manifestPath)) {
            return ["[{$slug}] missing manifest at modules/{$slug}/module.php"];
        }

        $raw = require $manifestPath;
        if (! is_array($raw)) {
            $violations[] = "[{$slug}] manifest must return an array";

            return $violations;
        }

        try {
            ModuleManifest::fromArray($raw, $slug);
        } catch (InvalidModuleSettingException $exception) {
            $violations[] = "[{$slug}] manifest: {$exception->getMessage()}";
        }

        $studly = Str::studly($slug);
        $providerClass = "Modules\\{$studly}\\{$studly}ServiceProvider";
        $providerPath = $moduleRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR."{$studly}ServiceProvider.php";

        if (! is_file($providerPath)) {
            $violations[] = "[{$slug}] missing service provider at modules/{$slug}/src/{$studly}ServiceProvider.php";
        }

        $bootstrapProviders = file_get_contents(base_path('bootstrap/providers.php')) ?: '';
        if (! str_contains($bootstrapProviders, $providerClass)) {
            $violations[] = "[{$slug}] {$providerClass} is not listed in bootstrap/providers.php";
        }

        $composer = json_decode(file_get_contents(base_path('composer.json')) ?: '{}', true);
        $psr4 = is_array($composer) ? ($composer['autoload']['psr-4'] ?? []) : [];
        $expectedPrefix = "Modules\\{$studly}\\";
        $expectedPath = "modules/{$slug}/src/";
        if (! isset($psr4[$expectedPrefix]) || $psr4[$expectedPrefix] !== $expectedPath) {
            $violations[] = "[{$slug}] composer.json needs \"{$expectedPrefix}\": \"{$expectedPath}\"";
        }

        $routesWeb = $moduleRoot.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'web.php';
        $routeFiles = glob($moduleRoot.DIRECTORY_SEPARATOR.'routes'.DIRECTORY_SEPARATOR.'*.php') ?: [];
        if (! is_file($routesWeb) && $routeFiles === []) {
            $violations[] = "[{$slug}] missing routes (expected modules/{$slug}/routes/web.php)";
        }

        if (is_file($providerPath)) {
            $providerSource = file_get_contents($providerPath) ?: '';
            if (! str_contains($providerSource, "'intranet.menu'") && ! str_contains($providerSource, '"intranet.menu"')) {
                $violations[] = "[{$slug}] service provider must tag a MenuContributor with intranet.menu";
            } elseif (! self::menuContributorPresent($moduleRoot, $studly)) {
                $violations[] = "[{$slug}] missing Navigation/*MenuContributor.php implementing BelongsToModule";
            }
        }

        return $violations;
    }

    private static function menuContributorPresent(string $moduleRoot, string $studly): bool
    {
        $navigationDir = $moduleRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Navigation';
        foreach (glob($navigationDir.DIRECTORY_SEPARATOR.'*MenuContributor.php') ?: [] as $path) {
            $source = file_get_contents($path) ?: '';
            if (
                str_contains($source, 'implements')
                && str_contains($source, 'MenuContributor')
                && str_contains($source, 'BelongsToModule')
            ) {
                return true;
            }
        }

        $fallback = $moduleRoot.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Navigation'.DIRECTORY_SEPARATOR."{$studly}MenuContributor.php";

        return is_file($fallback);
    }
}
