<?php

namespace App\Core\Appearance;

enum AppearanceTheme: string
{
    case Glass = 'glass';
    case Jungle = 'jungle';

    public function label(): string
    {
        return match ($this) {
            self::Glass => 'Glass',
            self::Jungle => 'Jungle',
        };
    }
}
