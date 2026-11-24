<?php

namespace App\Core\Legal;

final class LegalPageVisibility
{
    public const Everyone = 'everyone';

    public const SignedIn = 'signed_in';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::Everyone, self::SignedIn];
    }
}
