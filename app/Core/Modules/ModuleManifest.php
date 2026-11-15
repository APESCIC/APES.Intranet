<?php

namespace App\Core\Modules;

final readonly class ModuleManifest
{
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
