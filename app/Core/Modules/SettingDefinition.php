<?php

namespace App\Core\Modules;

final readonly class SettingDefinition
{
    /**
     * @param  list<string>  $options
     */
    public function __construct(
        public string $key,
        public SettingType $type,
        public mixed $default,
        public string $label,
        public array $options = [],
    ) {}

    /**
     * @param  array<string, mixed>  $raw
     */
    public static function fromArray(string $key, array $raw): self
    {
        $typeValue = $raw['type'] ?? null;
        if (! is_string($typeValue)) {
            throw new InvalidModuleSettingException("Setting [{$key}] is missing a type.");
        }

        $type = SettingType::tryFrom($typeValue);
        if ($type === null) {
            throw new InvalidModuleSettingException("Setting [{$key}] has an unknown type [{$typeValue}].");
        }

        $label = $raw['label'] ?? $key;
        if (! is_string($label) || $label === '') {
            throw new InvalidModuleSettingException("Setting [{$key}] needs a label.");
        }

        $options = [];
        if ($type === SettingType::Enum) {
            $rawOptions = $raw['options'] ?? [];
            if (! is_array($rawOptions) || $rawOptions === []) {
                throw new InvalidModuleSettingException("Setting [{$key}] of type enum needs options.");
            }
            foreach ($rawOptions as $option) {
                if (! is_string($option) || $option === '') {
                    throw new InvalidModuleSettingException("Setting [{$key}] has an invalid enum option.");
                }
                $options[] = $option;
            }
        }

        $default = array_key_exists('default', $raw) ? $raw['default'] : self::defaultFor($type);

        return new self($key, $type, $default, $label, $options);
    }

    private static function defaultFor(SettingType $type): mixed
    {
        return match ($type) {
            SettingType::String, SettingType::Url, SettingType::Enum => '',
            SettingType::Int => 0,
            SettingType::Bool => false,
            SettingType::SecretRef => ['env' => null, 'vault' => null],
        };
    }
}
