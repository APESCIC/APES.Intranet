<?php

namespace App\Core\Modules;

final readonly class ModuleManifest
{
    /** SemVer core: MAJOR.MINOR.PATCH with optional pre-release and build metadata. */
    public const VERSION_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    /**
     * @param  list<string>  $dependsOn
     * @param  list<string>  $cloudronAddons
     * @param  array<string, SettingDefinition>  $settings
     */
    public function __construct(
        public string $slug,
        public string $name,
        public ModuleKind $kind,
        public string $description,
        public string $version,
        public bool $defaultEnabled,
        public array $dependsOn = [],
        public array $cloudronAddons = [],
        public array $settings = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(array $raw, string $expectedSlug): self
    {
        $slug = $raw['slug'] ?? null;
        if (! is_string($slug) || $slug === '' || $slug !== $expectedSlug) {
            throw new InvalidModuleSettingException(
                "Manifest slug must be [{$expectedSlug}].",
            );
        }

        $name = $raw['name'] ?? null;
        if (! is_string($name) || $name === '') {
            throw new InvalidModuleSettingException("Manifest [{$slug}] needs a name.");
        }

        $kindValue = $raw['kind'] ?? ModuleKind::Module->value;
        if (! is_string($kindValue)) {
            throw new InvalidModuleSettingException("Manifest [{$slug}] has an invalid kind.");
        }
        $kind = ModuleKind::tryFrom($kindValue);
        if ($kind === null) {
            throw new InvalidModuleSettingException("Manifest [{$slug}] has an unknown kind [{$kindValue}].");
        }

        $description = $raw['description'] ?? '';
        if (! is_string($description)) {
            throw new InvalidModuleSettingException("Manifest [{$slug}] description must be a string.");
        }

        $version = $raw['version'] ?? null;
        if (! is_string($version) || $version === '' || preg_match(self::VERSION_PATTERN, $version) !== 1) {
            throw new InvalidModuleSettingException(
                "Manifest [{$slug}] needs a valid SemVer version (for example 1.0.0).",
            );
        }

        $defaultEnabled = array_key_exists('default_enabled', $raw)
            ? (bool) $raw['default_enabled']
            : true;

        $dependsOn = self::stringList($raw['depends_on'] ?? [], "Manifest [{$slug}] depends_on");
        $cloudronAddons = self::stringList($raw['cloudron_addons'] ?? [], "Manifest [{$slug}] cloudron_addons");

        $settings = [];
        $rawSettings = $raw['settings'] ?? [];
        if (! is_array($rawSettings)) {
            throw new InvalidModuleSettingException("Manifest [{$slug}] settings must be an array.");
        }
        foreach ($rawSettings as $key => $definition) {
            if (! is_string($key) || $key === '' || ! is_array($definition)) {
                throw new InvalidModuleSettingException("Manifest [{$slug}] has an invalid settings entry.");
            }
            $settings[$key] = SettingDefinition::fromArray($key, $definition);
        }

        return new self(
            $slug,
            $name,
            $kind,
            $description,
            $version,
            $defaultEnabled,
            $dependsOn,
            $cloudronAddons,
            $settings,
        );
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value, string $label): array
    {
        if (! is_array($value)) {
            throw new InvalidModuleSettingException("{$label} must be a list of strings.");
        }

        $list = [];
        foreach ($value as $item) {
            if (! is_string($item) || $item === '') {
                throw new InvalidModuleSettingException("{$label} must be a list of strings.");
            }
            $list[] = $item;
        }

        return $list;
    }
}
