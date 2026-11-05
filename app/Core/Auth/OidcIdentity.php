<?php

namespace App\Core\Auth;

final readonly class OidcIdentity
{
    /**
     * @param  int|null  $authTime  When the person last signed in to Cloudron (the `auth_time` claim), as a Unix time.
     */
    public function __construct(
        public ?string $subject,
        public ?string $email,
        public ?string $name,
        public ?int $authTime = null,
    ) {}
}
