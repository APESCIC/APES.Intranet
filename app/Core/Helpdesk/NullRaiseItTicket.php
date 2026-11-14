<?php

namespace App\Core\Helpdesk;

use App\Core\Helpdesk\Contracts\RaiseItTicket;
use App\Models\User;
use RuntimeException;

/**
 * Default when Helpdesk is not bound: raising an IT ticket is unavailable.
 */
final class NullRaiseItTicket implements RaiseItTicket
{
    public function raise(User $requester, string $subject, string $body, string $categorySlug = 'sign-in'): int
    {
        throw new RuntimeException('The IT help desk is not available.');
    }
}
