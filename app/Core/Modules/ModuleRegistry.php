<?php

namespace App\Core\Modules;

use App\Core\Audit\AuditLogger;
use App\Core\Modules\Models\ModuleSetting;
use App\Core\Modules\Models\ModuleState;
use App\Core\System\CloudronAddons;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

final class ModuleRegistry
{
    public const CACHE_STATES = 'core.modules.states';

    public const CACHE_SETTINGS_PREFIX = 'core.modules.settings.';

    /**
     * Reserved names that are never modules and cannot be enabled or disabled.
     *
     * @var list<string>
     */
    public const CORE_SLUGS = ['core', 'auth', 'audit', 'settings', 'health', 'shell'];

    /** @var array<string, ModuleManifest>|null */
    private ?array $manifests = null;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CacheRepository $cache,
        private readonly string $modulesPath = '',
    ) {}

    public function enabled(string $slug): bool
    {
        $states = $this->cachedStates();

        if (array_key_exists($slug, $states)) {
            return $states[$slug];
        }

        $manifest = $this->manifest($slug);

        return $manifest !== null ? $manifest->defaultEnabled : false;
    }

    public function manifest(string $slug): ?ModuleManifest
    {
        return $this->allManifests()[$slug] ?? null;
    }

    /**
     * @return Collection<string, ModuleManifest>
     */
    public function manifests(): Collection
    {
        return collect($this->allManifests());
    }

    public function enable(string $slug, string $reason, ?User $actor = null): void
    {
        $this->setEnabled($slug, true, $reason, $actor);
    }

    public function disable(string $slug, string $reason, ?User $actor = null): void
    {
        $this->setEnabled($slug, false, $reason, $actor);
    }

    public function setting(string $slug, string $key): mixed
    {
        $settings = $this->settings($slug);

        if (! array_key_exists($key, $settings)) {
            throw new InvalidModuleSettingException("Unknown setting [{$key}] for module [{$slug}].");
        }

        return $settings[$key];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(string $slug): array
    {
        $manifest = $this->requireManifest($slug);

        /** @var array<string, mixed> $stored */
        $stored = $this->cache->remember(
            self::CACHE_SETTINGS_PREFIX.$slug,
            now()->addHour(),
            function () use ($slug): array {
                return ModuleSetting::query()
                    ->where('slug', $slug)
                    ->get()
                    ->mapWithKeys(fn (ModuleSetting $row) => [$row->key => $row->value])
                    ->all();
            },
        );

        $merged = [];
        foreach ($manifest->settings as $key => $definition) {
            $merged[$key] = array_key_exists($key, $stored) ? $stored[$key] : $definition->default;
        }

        return $merged;
    }

    public function setSetting(string $slug, string $key, mixed $value): void
    {
        $manifest = $this->requireManifest($slug);
        $definition = $manifest->settings[$key] ?? null;
        if ($definition === null) {
            throw new InvalidModuleSettingException("Unknown setting [{$key}] for module [{$slug}].");
        }

        $validated = $this->validateSetting($definition, $value);

        ModuleSetting::query()->updateOrCreate(
            ['slug' => $slug, 'key' => $key],
            ['value' => $validated],
        );

        $this->cache->forget(self::CACHE_SETTINGS_PREFIX.$slug);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function setSettings(string $slug, array $values): void
    {
        foreach ($values as $key => $value) {
            $this->setSetting($slug, $key, $value);
        }
    }

    /**
     * Whether a secret_ref setting has both an env key and a vault item name.
     * Never returns the referenced secret value.
     */
    public function secretRefIsSet(string $slug, string $key): bool
    {
        $manifest = $this->requireManifest($slug);
        $definition = $manifest->settings[$key] ?? null;
        if ($definition === null || $definition->type !== SettingType::SecretRef) {
            throw new InvalidModuleSettingException("Setting [{$key}] on [{$slug}] is not a secret_ref.");
        }

        $value = $this->setting($slug, $key);
        if (! is_array($value)) {
            return false;
        }

        $env = $value['env'] ?? null;
        $vault = $value['vault'] ?? null;

        return is_string($env) && $env !== '' && is_string($vault) && $vault !== '';
    }

    public function forgetCache(): void
    {
        $this->cache->forget(self::CACHE_STATES);
        foreach (array_keys($this->allManifests()) as $slug) {
            $this->cache->forget(self::CACHE_SETTINGS_PREFIX.$slug);
        }
    }

    /**
     * Reload manifests from disk (tests / after installing a module package).
     */
    public function refreshManifests(): void
    {
        $this->manifests = null;
        $this->forgetCache();
    }

    public function isCoreSlug(string $slug): bool
    {
        return in_array(strtolower($slug), self::CORE_SLUGS, true);
    }

    /**
     * Enabled modules that list $slug in depends_on (so $slug must stay on).
     *
     * @return Collection<int, ModuleManifest>
     */
    public function enabledDependents(string $slug): Collection
    {
        return $this->manifests()
            ->filter(fn (ModuleManifest $manifest) => in_array($slug, $manifest->dependsOn, true))
            ->filter(fn (ModuleManifest $manifest) => $this->enabled($manifest->slug))
            ->values();
    }

    /**
     * Cloudron addon keys the module needs that look missing locally.
     *
     * @return list<string>
     */
    public function missingCloudronAddons(string $slug): array
    {
        $manifest = $this->requireManifest($slug);
        $missing = [];

        foreach ($manifest->cloudronAddons as $addon) {
            if (! CloudronAddons::looksPresent($addon)) {
                $missing[] = $addon;
            }
        }

        return $missing;
    }

    private function setEnabled(string $slug, bool $enabled, string $reason, ?User $actor): void
    {
        if ($this->isCoreSlug($slug)) {
            throw new InvalidArgumentException('Core cannot be disabled.');
        }

        $this->requireManifest($slug);

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is required when changing module state.');
        }

        if (! $enabled) {
            $dependents = $this->enabledDependents($slug);
            if ($dependents->isNotEmpty()) {
                $names = $dependents->pluck('name')->implode(', ');

                throw new InvalidArgumentException(
                    "Cannot disable [{$slug}] while dependent modules are still enabled: {$names}.",
                );
            }
        }

        $state = ModuleState::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'enabled' => $enabled,
                'reason' => $reason,
                'changed_by' => $actor?->getKey(),
                'changed_at' => now(),
            ],
        );

        $this->cache->forget(self::CACHE_STATES);

        $this->audit->record(
            $enabled ? 'admin.module.enabled' : 'admin.module.disabled',
            $state,
            ['reason' => $reason],
            $actor,
        );
    }

    /**
     * @return array<string, bool>
     */
    private function cachedStates(): array
    {
        /** @var array<string, bool> */
        return $this->cache->remember(
            self::CACHE_STATES,
            now()->addHour(),
            function (): array {
                return ModuleState::query()
                    ->get()
                    ->mapWithKeys(fn (ModuleState $state) => [$state->slug => $state->enabled])
                    ->all();
            },
        );
    }

    private function requireManifest(string $slug): ModuleManifest
    {
        $manifest = $this->manifest($slug);
        if ($manifest === null) {
            throw new InvalidArgumentException("Unknown module [{$slug}].");
        }

        return $manifest;
    }

    /**
     * @return array<string, ModuleManifest>
     */
    private function allManifests(): array
    {
        if ($this->manifests !== null) {
            return $this->manifests;
        }

        $path = $this->modulesPath !== '' ? $this->modulesPath : base_path('modules');
        $manifests = [];

        if (! File::isDirectory($path)) {
            return $this->manifests = [];
        }

        foreach (File::directories($path) as $directory) {
            $slug = basename($directory);
            $file = $directory.DIRECTORY_SEPARATOR.'module.php';
            if (! File::isFile($file)) {
                continue;
            }

            /** @var mixed $raw */
            $raw = require $file;
            if (! is_array($raw)) {
                throw new InvalidModuleSettingException("Manifest for [{$slug}] must return an array.");
            }

            $manifests[$slug] = ModuleManifest::fromArray($raw, $slug);
        }

        ksort($manifests);

        return $this->manifests = $manifests;
    }

    private function validateSetting(SettingDefinition $definition, mixed $value): mixed
    {
        return match ($definition->type) {
            SettingType::String => $this->validateString($definition, $value),
            SettingType::Int => $this->validateInt($definition, $value),
            SettingType::Bool => $this->validateBool($definition, $value),
            SettingType::Enum => $this->validateEnum($definition, $value),
            SettingType::Url => $this->validateUrl($definition, $value),
            SettingType::SecretRef => $this->validateSecretRef($definition, $value),
        };
    }

    private function validateString(SettingDefinition $definition, mixed $value): string
    {
        if (! is_string($value)) {
            throw new InvalidModuleSettingException("Setting [{$definition->key}] must be a string.");
        }

        return $value;
    }

    private function validateInt(SettingDefinition $definition, mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new InvalidModuleSettingException("Setting [{$definition->key}] must be an integer.");
    }

    private function validateBool(SettingDefinition $definition, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        throw new InvalidModuleSettingException("Setting [{$definition->key}] must be a boolean.");
    }

    private function validateEnum(SettingDefinition $definition, mixed $value): string
    {
        if (! is_string($value) || ! in_array($value, $definition->options, true)) {
            throw new InvalidModuleSettingException(
                "Setting [{$definition->key}] must be one of: ".implode(', ', $definition->options).'.',
            );
        }

        return $value;
    }

    private function validateUrl(SettingDefinition $definition, mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            throw new InvalidModuleSettingException("Setting [{$definition->key}] must be a URL.");
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidModuleSettingException("Setting [{$definition->key}] must be a valid URL.");
        }

        return $value;
    }

    /**
     * @return array{env: ?string, vault: ?string}
     */
    private function validateSecretRef(SettingDefinition $definition, mixed $value): array
    {
        if (! is_array($value)) {
            throw new InvalidModuleSettingException(
                "Setting [{$definition->key}] must be a secret_ref with env and vault names only.",
            );
        }

        $allowed = ['env', 'vault'];
        foreach (array_keys($value) as $key) {
            if (! in_array($key, $allowed, true)) {
                throw new InvalidModuleSettingException(
                    "Setting [{$definition->key}] secret_ref only accepts env and vault names, never secret values.",
                );
            }
        }

        $env = $value['env'] ?? null;
        $vault = $value['vault'] ?? null;

        if ($env !== null && (! is_string($env) || ! $this->looksLikeEnvKey($env))) {
            throw new InvalidModuleSettingException(
                "Setting [{$definition->key}] env must be an environment key name (UPPER_SNAKE), not a secret value.",
            );
        }

        if ($vault !== null && (! is_string($vault) || trim($vault) === '')) {
            throw new InvalidModuleSettingException(
                "Setting [{$definition->key}] vault must be a Vault item name, not a secret value.",
            );
        }

        if (is_string($vault) && $this->looksLikeSecretValue($vault)) {
            throw new InvalidModuleSettingException(
                "Setting [{$definition->key}] vault must be a Vault item name, never a secret value.",
            );
        }

        return [
            'env' => is_string($env) && $env !== '' ? $env : null,
            'vault' => is_string($vault) && trim($vault) !== '' ? trim($vault) : null,
        ];
    }

    private function looksLikeEnvKey(string $value): bool
    {
        return preg_match('/^[A-Z][A-Z0-9_]*$/', $value) === 1;
    }

    private function looksLikeSecretValue(string $value): bool
    {
        if (strlen($value) > 120) {
            return true;
        }

        if (preg_match('/\s/', $value) === 1 && preg_match('/^[A-Za-z0-9 ._\-\/()]+$/', $value) !== 1) {
            return true;
        }

        return (bool) preg_match('/(?:sk-|ghp_|xox[baprs]-|Bearer\s)/i', $value);
    }
}
