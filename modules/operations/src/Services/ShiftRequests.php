<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\Shift;
use Modules\Operations\Models\ShiftRequest;
use Modules\Operations\Support\Notifier;
use Modules\Operations\Support\TeamAccess;
use RuntimeException;

/**
 * Claims and swaps. Members ask; a manager of the site's team approves or
 * declines. Every step is audited and both members and the managers are told.
 */
final class ShiftRequests
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function claim(Shift $shift, User $member, ?string $note = null): ShiftRequest
    {
        $shift->loadMissing(['rotaWeek', 'site']);

        if (! $shift->isOpen() || ! $shift->rotaWeek->isPublished() || $shift->starts_at->isPast()) {
            throw new RuntimeException('This shift is not open to claim.');
        }

        if ($this->hasPending($shift, $member)) {
            throw new RuntimeException('You have already asked for this shift.');
        }

        $request = ShiftRequest::query()->create([
            'type' => ShiftRequest::TYPE_CLAIM,
            'shift_id' => $shift->id,
            'requester_id' => $member->getKey(),
            'status' => ShiftRequest::STATUS_PENDING,
            'note' => $note,
        ]);

        $this->audit->record('operations.shift_claim_requested', $request, ['shift_id' => $shift->id], $member);
        $this->tell($request, 'Shift claim waiting for approval', $request->load('requester', 'shift.site')->summary().'. A manager needs to approve it before the rota changes.', $member);

        return $request;
    }

    public function swap(Shift $mine, Shift $theirs, User $member, ?string $note = null): ShiftRequest
    {
        $mine->loadMissing('rotaWeek');
        $theirs->loadMissing(['rotaWeek', 'member']);

        if ((int) $mine->user_id !== (int) $member->getKey()
            || $theirs->user_id === null
            || (int) $theirs->user_id === (int) $member->getKey()
            || $mine->site_id !== $theirs->site_id
            || ! $mine->rotaWeek->isPublished()
            || ! $theirs->rotaWeek->isPublished()
            || $mine->starts_at->isPast()
            || $theirs->starts_at->isPast()) {
            throw new RuntimeException('These shifts cannot be swapped.');
        }

        if ($this->hasPending($mine, $member)) {
            throw new RuntimeException('You already have a swap waiting for this shift.');
        }

        $request = ShiftRequest::query()->create([
            'type' => ShiftRequest::TYPE_SWAP,
            'shift_id' => $mine->id,
            'target_shift_id' => $theirs->id,
            'requester_id' => $member->getKey(),
            'status' => ShiftRequest::STATUS_PENDING,
            'note' => $note,
        ]);

        $this->audit->record('operations.shift_swap_requested', $request, ['shift_id' => $mine->id, 'target_shift_id' => $theirs->id], $member);
        $request->load('requester', 'shift.site', 'targetShift.member');
        $this->tell($request, 'Shift swap waiting for approval', $request->summary().'. A manager needs to approve it before the rota changes.', $member, $theirs->member);

        return $request;
    }

    public function decide(ShiftRequest $request, User $manager, bool $approve, ?string $note = null): void
    {
        if (! $request->isPending()) {
            throw new RuntimeException('This request has already been decided.');
        }

        if ((int) $request->requester_id === (int) $manager->getKey()) {
            throw new RuntimeException('Another manager needs to decide your own request.');
        }

        DB::transaction(function () use ($request, $manager, $approve, $note) {
            $request->load(['shift', 'targetShift']);
            $shift = $request->shift;
            $target = $request->targetShift;

            if ($approve) {
                if ($request->type === ShiftRequest::TYPE_CLAIM) {
                    if (! $shift->isOpen()) {
                        throw new RuntimeException('This shift has already been filled.');
                    }
                    $shift->update(['user_id' => $request->requester_id]);
                    $this->closeOtherClaims($request);
                } else {
                    if ($target === null || (int) $shift->user_id !== (int) $request->requester_id || $target->user_id === null) {
                        throw new RuntimeException('One of these shifts has changed since the swap was asked for.');
                    }
                    $targetMember = $target->user_id;
                    $target->update(['user_id' => $shift->user_id]);
                    $shift->update(['user_id' => $targetMember]);
                }
            }

            $request->forceFill([
                'status' => $approve ? ShiftRequest::STATUS_APPROVED : ShiftRequest::STATUS_DECLINED,
                'decided_by' => $manager->getKey(),
                'decided_at' => now(),
                'decision_note' => $note,
            ])->save();
        });

        $request->refresh()->load('requester', 'shift.site', 'shift.member', 'targetShift.member');

        $this->audit->record('operations.shift_'.$request->type.'_'.$request->status, $request, [
            'shift_id' => $request->shift_id,
            'target_shift_id' => $request->target_shift_id,
            'shift_user_id' => $request->shift->user_id,
            'target_user_id' => $request->targetShift?->user_id,
        ], $manager);

        $verb = $approve ? 'approved' : 'declined';
        $other = $request->type === ShiftRequest::TYPE_SWAP
            ? ($approve ? $request->shift->member : $request->targetShift?->member)
            : null;
        $this->tell(
            $request,
            'Shift '.$request->type.' '.$verb,
            ucfirst($request->type).' '.$verb.' by '.$manager->name.': '.$this->describe($request).($note ? ' Note: '.$note : ''),
            $request->requester,
            $other,
        );
    }

    public function cancel(ShiftRequest $request, User $member): void
    {
        if (! $request->isPending() || (int) $request->requester_id !== (int) $member->getKey()) {
            throw new RuntimeException('You can only cancel your own request while it is waiting.');
        }

        $request->forceFill(['status' => ShiftRequest::STATUS_CANCELLED])->save();
        $this->audit->record('operations.shift_'.$request->type.'_cancelled', $request, ['shift_id' => $request->shift_id], $member);
    }

    private function hasPending(Shift $shift, User $member): bool
    {
        return ShiftRequest::query()
            ->where('shift_id', $shift->id)
            ->where('requester_id', $member->getKey())
            ->where('status', ShiftRequest::STATUS_PENDING)
            ->exists();
    }

    private function closeOtherClaims(ShiftRequest $approved): void
    {
        ShiftRequest::query()
            ->where('shift_id', $approved->shift_id)
            ->where('type', ShiftRequest::TYPE_CLAIM)
            ->where('status', ShiftRequest::STATUS_PENDING)
            ->whereKeyNot($approved->getKey())
            ->update(['status' => ShiftRequest::STATUS_DECLINED, 'decision_note' => 'Shift filled by another claim.', 'decided_at' => now()]);
    }

    private function describe(ShiftRequest $request): string
    {
        return $request->type === ShiftRequest::TYPE_CLAIM
            ? $request->shift->label().' at '.$request->shift->site->name.'.'
            : $request->shift->label().' and '.$request->targetShift?->label().' at '.$request->shift->site->name.'.';
    }

    private function tell(ShiftRequest $request, string $subject, string $line, ?User ...$members): void
    {
        $request->loadMissing('shift.site');
        $people = TeamAccess::approversFor($request->shift->site->team, $request->requester)
            ->concat(array_filter($members));

        Notifier::send($people, $subject, [$line], route('operations.rota.requests.index'));
    }
}
