<?php

namespace App\Core\Appearance;

enum AppearanceMode: string
{
    case Light = 'light';
    case Dark = 'dark';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Light => 'Light',
            self::Dark => 'Dark',
            self::System => 'System',
        };
    }
}
