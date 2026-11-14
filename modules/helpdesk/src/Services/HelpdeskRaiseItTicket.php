<?php

namespace Modules\Helpdesk\Services;

use App\Core\Helpdesk\Contracts\RaiseItTicket;
use App\Models\User;
use Modules\Helpdesk\Models\Category;
use RuntimeException;

final class HelpdeskRaiseItTicket implements RaiseItTicket
{
    public function __construct(private readonly Tickets $tickets) {}

    public function raise(User $requester, string $subject, string $body, string $categorySlug = 'sign-in'): int
    {
        $category = Category::query()->active()->where('slug', $categorySlug)->first();
        if ($category === null) {
            throw new RuntimeException('IT help desk category "'.$categorySlug.'" is not set up.');
        }

        $ticket = $this->tickets->raise($requester, $category, $subject, $body);

        return (int) $ticket->getKey();
    }
}
