<?php

namespace App\Core\Legal;

final class LegalPageVersionStatus
{
    public const Draft = 'draft';

    public const Published = 'published';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::Draft, self::Published];
    }
}
