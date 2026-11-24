<?php

namespace App\Core\Auth;

/**
 * Which directory backs sign-in groups and nightly sync for this release.
 */
final class DirectorySource
{
    public const CLOUDRON = 'cloudron';

    public const LDAP = 'ldap';

    public static function current(): string
    {
        $value = strtolower(trim((string) config('intranet.directory_source', self::CLOUDRON)));

        return $value === self::LDAP ? self::LDAP : self::CLOUDRON;
    }

    public static function isCloudron(): bool
    {
        return self::current() === self::CLOUDRON;
    }

    public static function isLdap(): bool
    {
        return self::current() === self::LDAP;
    }

    public static function syncAuditSource(): string
    {
        return self::isCloudron() ? 'cloudron_api' : 'ldap';
    }

    /**
     * Whether the LDAP addon variables are present for a switch-back or shadow compare.
     */
    public static function ldapConfigured(): bool
    {
        return trim((string) config('intranet.ldap.host')) !== ''
            && trim((string) config('intranet.ldap.bind_dn')) !== '';
    }
}
