<?php

namespace App\Core\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $event, ?Model $subject = null, array $context = [], ?User $actor = null): AuditLog
    {
        $actor ??= $this->request->user();

        return AuditLog::query()->create([
            'occurred_at' => now(),
            'actor_id' => $actor instanceof User ? $actor->getKey() : null,
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 250, ''),
            'context' => $context === [] ? null : $context,
        ]);
    }

    /**
     * Log a role change only when the roles actually changed.
     *
     * @param  list<string>  $before
     * @param  list<string>  $after
     */
    public function recordRoleChange(User $user, array $before, array $after, string $source): void
    {
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        if ($added === [] && $removed === []) {
            return;
        }

        $this->record('roles.changed', $user, [
            'added' => $added,
            'removed' => $removed,
            'source' => $source,
        ]);
    }
}
