<?php

namespace App\Core\System;

/**
 * The Cloudron addon variables the intranet reads, and which of them are
 * secret. Config reads these first, so a restore, clone or password rotation
 * never leaves the app using a stale copy.
 */
final class CloudronAddons
{
    /**
     * `.env` keys that must never hold a value on Cloudron: the addons supply them.
     */
    public const FORBIDDEN_ENV_KEYS = ['DB_PASSWORD', 'REDIS_PASSWORD', 'MAIL_PASSWORD', 'LDAP_BIND_PASSWORD'];

    /**
     * @return array<string, array{label: string, variables: list<string>, secrets: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            'mysql' => [
                'label' => 'MySQL',
                'variables' => ['CLOUDRON_MYSQL_HOST', 'CLOUDRON_MYSQL_PORT', 'CLOUDRON_MYSQL_DATABASE', 'CLOUDRON_MYSQL_USERNAME', 'CLOUDRON_MYSQL_PASSWORD', 'CLOUDRON_MYSQL_URL'],
                'secrets' => ['CLOUDRON_MYSQL_PASSWORD', 'CLOUDRON_MYSQL_URL'],
            ],
            'redis' => [
                'label' => 'Redis',
                'variables' => ['CLOUDRON_REDIS_HOST', 'CLOUDRON_REDIS_PORT', 'CLOUDRON_REDIS_PASSWORD', 'CLOUDRON_REDIS_URL'],
                'secrets' => ['CLOUDRON_REDIS_PASSWORD', 'CLOUDRON_REDIS_URL'],
            ],
            'sendmail' => [
                'label' => 'Sendmail',
                'variables' => ['CLOUDRON_MAIL_SMTP_SERVER', 'CLOUDRON_MAIL_SMTP_PORT', 'CLOUDRON_MAIL_SMTP_USERNAME', 'CLOUDRON_MAIL_SMTP_PASSWORD', 'CLOUDRON_MAIL_FROM', 'CLOUDRON_MAIL_DOMAIN'],
                'secrets' => ['CLOUDRON_MAIL_SMTP_PASSWORD'],
            ],
            'ldap' => [
                'label' => 'LDAP',
                'variables' => ['CLOUDRON_LDAP_HOST', 'CLOUDRON_LDAP_PORT', 'CLOUDRON_LDAP_USERS_BASE_DN', 'CLOUDRON_LDAP_GROUPS_BASE_DN', 'CLOUDRON_LDAP_BIND_DN', 'CLOUDRON_LDAP_BIND_PASSWORD'],
                'secrets' => ['CLOUDRON_LDAP_BIND_PASSWORD'],
            ],
            'oidc' => [
                'label' => 'OIDC client (groups claim)',
                'variables' => ['OIDC_ISSUER', 'OIDC_CLIENT_ID', 'OIDC_CLIENT_SECRET', 'OIDC_REDIRECT_URI', 'OIDC_SCOPES'],
                'secrets' => ['OIDC_CLIENT_SECRET'],
            ],
            'cloudron_api' => [
                'label' => 'Cloudron API (directory sync)',
                'variables' => ['CLOUDRON_API_BASE', 'CLOUDRON_API_TOKEN'],
                'secrets' => ['CLOUDRON_API_TOKEN'],
            ],
        ];
    }

    /**
     * Whether any of the addon's variables look set in the process environment.
     *
     * Matches System status / config building: values may appear only via
     * getenv() on Cloudron (not in $_ENV / $_SERVER).
     */
    public static function looksPresent(string $addon): bool
    {
        $definition = self::definitions()[$addon] ?? null;
        if ($definition === null) {
            return false;
        }

        /** @var list<string>|null $setNames */
        $setNames = config('intranet.cloudron_variables');
        if (is_array($setNames)) {
            foreach ($definition['variables'] as $variable) {
                if (in_array($variable, $setNames, true)) {
                    return true;
                }
            }
        }

        foreach ($definition['variables'] as $variable) {
            if (self::variableLooksSet($variable)) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when a process env variable has a non-empty string value.
     */
    public static function variableLooksSet(string $variable): bool
    {
        foreach ([$_ENV[$variable] ?? null, $_SERVER[$variable] ?? null] as $value) {
            if (is_string($value) && $value !== '') {
                return true;
            }
        }

        $fromGetenv = getenv($variable);

        return is_string($fromGetenv) && $fromGetenv !== '';
    }

    /**
     * Keys in an env file that copy an addon password.
     *
     * @return list<string>
     */
    public static function forbiddenKeysIn(string $envContents): array
    {
        $found = [];

        foreach (self::FORBIDDEN_ENV_KEYS as $key) {
            if (preg_match('/^'.$key.'=(?!\s*$)(?!null\s*$)(?!""\s*$).+/m', $envContents) === 1) {
                $found[] = $key;
            }
        }

        return $found;
    }
}
