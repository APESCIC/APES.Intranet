<?php

namespace App\Core\Legal;

final class FooterLinkGroup
{
    public const Legal = 'legal';

    public const TrustAndHelp = 'trust_and_help';

    public const About = 'about';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::Legal, self::TrustAndHelp, self::About];
    }

    public static function label(string $group): string
    {
        return match ($group) {
            self::Legal => 'Legal',
            self::TrustAndHelp => 'Trust and help',
            self::About => 'About',
            default => $group,
        };
    }
}
