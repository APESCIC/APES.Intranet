<?php

namespace Modules\Communications\Support;

final class MicrosoftUrlAllowlist
{
    /**
     * @param  list<string>  $suffixes
     */
    public static function allows(string $url, array $suffixes): bool
    {
        if (! str_starts_with($url, 'https://')) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        foreach ($suffixes as $suffix) {
            $suffix = strtolower($suffix);
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }
}
