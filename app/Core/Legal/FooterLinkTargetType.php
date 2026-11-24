<?php

namespace App\Core\Legal;

final class FooterLinkTargetType
{
    public const LegalPage = 'legal_page';

    public const Route = 'route';

    public const Url = 'url';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::LegalPage, self::Route, self::Url];
    }
}
