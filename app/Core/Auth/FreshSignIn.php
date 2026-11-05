<?php

namespace App\Core\Auth;

use Illuminate\Contracts\Session\Session;

/**
 * Tracks when the signed-in person last signed in to Cloudron, so HR and
 * agreements pages can ask for a new sign-in after the configured window.
 */
final class FreshSignIn
{
    private const AUTH_TIME = 'intranet.auth_time';

    private const REQUESTED_AT = 'intranet.fresh_signin_requested_at';

    /** Clock skew allowed between Cloudron and the app, in seconds. */
    private const SKEW = 60;

    public function __construct(private readonly Session $session) {}

    public static function windowMinutes(): int
    {
        return (int) config('intranet.fresh_signin_minutes', 15);
    }

    public function record(?int $authTime): void
    {
        $this->session->put(self::AUTH_TIME, $authTime ?? now()->getTimestamp());
    }

    public function authTime(): ?int
    {
        $value = $this->session->get(self::AUTH_TIME);

        return is_int($value) ? $value : null;
    }

    public function isFresh(): bool
    {
        $authTime = $this->authTime();

        return $authTime !== null && $authTime >= now()->subMinutes(self::windowMinutes())->getTimestamp();
    }

    public function markRequested(): void
    {
        $this->session->put(self::REQUESTED_AT, now()->getTimestamp());
    }

    /**
     * Whether a forced sign-in was asked for, clearing the request.
     */
    public function pullRequestedAt(): ?int
    {
        $value = $this->session->pull(self::REQUESTED_AT);

        return is_int($value) ? $value : null;
    }

    /**
     * Whether Cloudron really asked the person to sign in after the request.
     */
    public static function honoured(int $requestedAt, ?int $authTime): bool
    {
        return $authTime !== null && $authTime >= $requestedAt - self::SKEW;
    }
}
