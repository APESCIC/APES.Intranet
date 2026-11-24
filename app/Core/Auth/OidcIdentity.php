<?php

namespace App\Core\Auth;

final readonly class OidcIdentity
{
    /**
     * @param  int|null  $authTime  When the person last signed in to Cloudron (the `auth_time` claim), as a Unix time.
     * @param  list<string>|null  $groups  Lower-case group names from the OIDC `groups` claim; null when the claim is missing.
     * @param  string|null  $preferredUsername  Cloudron username from `preferred_username`.
     */
    public function __construct(
        public ?string $subject,
        public ?string $email,
        public ?string $name,
        public ?int $authTime = null,
        public ?array $groups = null,
        public ?string $preferredUsername = null,
    ) {}
}
