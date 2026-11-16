<?php

namespace Modules\Learning;

enum EnrolmentMethod: string
{
    case Manual = 'manual';
    case Group = 'group';
    case Self = 'self';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Group => 'Canonical group',
            self::Self => 'Self-enrol',
        };
    }
}
