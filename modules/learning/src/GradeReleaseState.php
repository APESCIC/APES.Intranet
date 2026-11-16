<?php

namespace Modules\Learning;

enum GradeReleaseState: string
{
    case Draft = 'draft';
    case Marked = 'marked';
    case Moderated = 'moderated';
    case Released = 'released';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Marked => 'Marked',
            self::Moderated => 'Moderated',
            self::Released => 'Released',
        };
    }
}
