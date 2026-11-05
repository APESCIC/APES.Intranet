<?php

namespace App\Core\Auth\Cloudron;

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use LDAP\Connection;

class LdapDirectoryGroups implements DirectoryGroups
{
    public function groupsForEmail(string $email): array
    {
        if (! function_exists('ldap_connect')) {
            throw new DirectoryUnavailable('The PHP LDAP extension is not installed.');
        }

        $filter = $this->config('user_filter');
        $attribute = strtolower($this->config('group_attribute'));

        if (! str_contains($filter, '%s')) {
            throw new DirectoryUnavailable('LDAP user filter must contain %s.');
        }

        $ldap = $this->connect();

        try {
            $search = @ldap_search(
                $ldap,
                $this->config('base_dn'),
                sprintf($filter, ldap_escape($email, '', LDAP_ESCAPE_FILTER)),
                [$attribute],
                0,
                2,
                (int) config('intranet.ldap.timeout_seconds', 5),
            );

            if ($search === false) {
                throw new DirectoryUnavailable('LDAP search failed.');
            }

            $entries = ldap_get_entries($ldap, $search);
        } finally {
            @ldap_unbind($ldap);
        }

        if (! is_array($entries) || ($entries['count'] ?? 0) > 1) {
            throw new DirectoryUnavailable('LDAP search returned an unexpected result.');
        }

        if (($entries['count'] ?? 0) === 0) {
            return [];
        }

        $values = $entries[0][$attribute] ?? [];
        $groups = [];

        for ($i = 0; $i < (int) ($values['count'] ?? 0); $i++) {
            if (is_string($values[$i] ?? null)) {
                $groups[] = $this->groupName($values[$i]);
            }
        }

        return array_values(array_unique($groups));
    }

    /**
     * Binds with the service account and disconnects, for health checks.
     *
     * @throws DirectoryUnavailable
     */
    public function checkBind(): void
    {
        if (! function_exists('ldap_connect')) {
            throw new DirectoryUnavailable('The PHP LDAP extension is not installed.');
        }

        @ldap_unbind($this->connect());
    }

    private function connect(): Connection
    {
        $ldap = @ldap_connect('ldap://'.$this->config('host').':'.(int) config('intranet.ldap.port', 389));

        if ($ldap === false) {
            throw new DirectoryUnavailable('Unable to connect to LDAP.');
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, (int) config('intranet.ldap.timeout_seconds', 5));

        if (! @ldap_bind($ldap, $this->config('bind_dn'), $this->config('bind_password'))) {
            @ldap_unbind($ldap);

            throw new DirectoryUnavailable('LDAP bind failed.');
        }

        return $ldap;
    }

    /**
     * "cn=intranet.staff,ou=groups,dc=..." becomes "intranet.staff".
     */
    private function groupName(string $value): string
    {
        $first = explode(',', $value)[0];

        return strtolower(trim((string) preg_replace('/^cn=/i', '', trim($first))));
    }

    private function config(string $key): string
    {
        $value = config('intranet.ldap.'.$key);

        if (! is_string($value) || trim($value) === '') {
            throw new DirectoryUnavailable("LDAP {$key} is not configured.");
        }

        return trim($value);
    }
}
