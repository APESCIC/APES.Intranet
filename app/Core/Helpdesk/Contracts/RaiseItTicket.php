<?php

namespace App\Core\Helpdesk\Contracts;

use App\Models\User;
use RuntimeException;

/**
 * Raise a ticket on the IT help desk queue. Implemented by Helpdesk; HR uses
 * this for offboarding so it never imports Helpdesk models.
 */
interface RaiseItTicket
{
    /**
     * @return int The new ticket id
     *
     * @throws RuntimeException when the IT category is missing or raise fails
     */
    public function raise(User $requester, string $subject, string $body, string $categorySlug = 'sign-in'): int;
}
