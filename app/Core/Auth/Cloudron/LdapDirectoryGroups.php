<?php

namespace App\Core\Auth\Cloudron;

use App\Core\Auth\Contracts\DirectoryGroups;
use App\Core\Auth\Contracts\DirectoryRoster;
use App\Core\Auth\DirectoryPerson;
use App\Core\Auth\Exceptions\DirectoryUnavailable;
use LDAP\Connection;

class LdapDirectoryGroups implements DirectoryGroups, DirectoryRoster
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
                [$attribute, 'cn', 'displayname', 'mail', 'uid'],
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
     * @return list<DirectoryPerson>
     */
    public function listPeople(): array
    {
        if (! function_exists('ldap_connect')) {
            throw new DirectoryUnavailable('The PHP LDAP extension is not installed.');
        }

        $ldap = $this->connect();
        $attribute = strtolower($this->config('group_attribute'));

        try {
            // Read-only search of the users tree. Cloudron keeps disabled
            // accounts in LDAP; we treat a missing mail as inactive and leave
            // group membership to decide intranet access.
            $search = @ldap_search(
                $ldap,
                $this->config('base_dn'),
                '(objectClass=*)',
                ['mail', 'cn', 'displayname', 'uid', $attribute],
                0,
                0,
                (int) config('intranet.ldap.timeout_seconds', 5),
            );

            if ($search === false) {
                throw new DirectoryUnavailable('LDAP directory search failed.');
            }

            $entries = ldap_get_entries($ldap, $search);
        } finally {
            @ldap_unbind($ldap);
        }

        if (! is_array($entries)) {
            throw new DirectoryUnavailable('LDAP directory search returned an unexpected result.');
        }

        $people = [];

        for ($i = 0; $i < (int) ($entries['count'] ?? 0); $i++) {
            $entry = $entries[$i];
            $email = $this->firstAttribute($entry, 'mail');

            if ($email === null) {
                continue;
            }

            $groups = [];
            $values = $entry[$attribute] ?? [];

            for ($g = 0; $g < (int) ($values['count'] ?? 0); $g++) {
                if (is_string($values[$g] ?? null)) {
                    $groups[] = $this->groupName($values[$g]);
                }
            }

            $name = $this->firstAttribute($entry, 'displayname')
                ?? $this->firstAttribute($entry, 'cn')
                ?? $email;

            $people[] = new DirectoryPerson(
                email: strtolower($email),
                name: $name,
                username: $this->firstAttribute($entry, 'uid'),
                groups: array_values(array_unique($groups)),
                active: true,
            );
        }

        return $people;
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

    /**
     * @param  array<string, mixed>  $entry
     */
    private function firstAttribute(array $entry, string $name): ?string
    {
        $values = $entry[strtolower($name)] ?? null;

        if (! is_array($values) || (int) ($values['count'] ?? 0) < 1 || ! is_string($values[0] ?? null)) {
            return null;
        }

        $value = trim($values[0]);

        return $value === '' ? null : $value;
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
