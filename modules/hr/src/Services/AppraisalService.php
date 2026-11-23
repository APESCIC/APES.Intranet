<?php

namespace Modules\Hr\Services;

use App\Models\User;
use Modules\Hr\HrPermissions;
use Modules\Hr\Models\Appraisal;
use Modules\Hr\Models\AppraisalCycle;
use Modules\Hr\Models\AppraisalObjective;
use RuntimeException;

final class AppraisalService
{
    public function openCycle(string $name, int $year, string $startsOn, string $endsOn): AppraisalCycle
    {
        return AppraisalCycle::query()->create([
            'name' => $name,
            'year' => $year,
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'open' => true,
        ]);
    }

    public function startFor(AppraisalCycle $cycle, User $member, ?User $manager = null): Appraisal
    {
        if (! $cycle->open) {
            throw new RuntimeException('This appraisal cycle is closed.');
        }

        return Appraisal::query()->firstOrCreate(
            ['cycle_id' => $cycle->id, 'user_id' => $member->id],
            [
                'manager_id' => $manager !== null ? $manager->id : $member->manager_id,
                'status' => Appraisal::STATUS_DRAFT,
            ],
        );
    }

    public function saveSelfReview(Appraisal $appraisal, string $text, User $actor): Appraisal
    {
        $this->assertEditable($appraisal, $actor);
        if ((int) $actor->id !== (int) $appraisal->user_id && ! $actor->can(HrPermissions::ACCESS)) {
            throw new RuntimeException('Only the member can write the self review.');
        }

        $appraisal->fill([
            'self_review' => $text,
            'status' => Appraisal::STATUS_SELF_SUBMITTED,
        ])->save();

        return $appraisal->refresh();
    }

    public function saveManagerReview(Appraisal $appraisal, string $text, User $actor): Appraisal
    {
        $this->assertEditable($appraisal, $actor);
        $isManager = (int) $actor->id === (int) $appraisal->manager_id
            || (int) $actor->id === (int) $appraisal->user?->manager_id;
        if (! $isManager && ! $actor->can(HrPermissions::ACCESS)) {
            throw new RuntimeException('Only the manager or HR can write the manager review.');
        }

        $appraisal->fill([
            'manager_review' => $text,
            'status' => Appraisal::STATUS_MANAGER_SUBMITTED,
        ])->save();

        return $appraisal->refresh();
    }

    public function addObjective(Appraisal $appraisal, string $title, ?string $notes, User $actor): AppraisalObjective
    {
        $this->assertEditable($appraisal, $actor);

        $order = (int) $appraisal->objectives()->max('sort_order') + 10;

        return AppraisalObjective::query()->create([
            'appraisal_id' => $appraisal->id,
            'title' => $title,
            'notes' => $notes,
            'sort_order' => $order,
        ]);
    }

    public function signOff(Appraisal $appraisal, User $actor, string $as): Appraisal
    {
        $this->assertEditable($appraisal, $actor);

        if ($as === 'member') {
            if ((int) $actor->id !== (int) $appraisal->user_id && ! $actor->can(HrPermissions::ACCESS)) {
                throw new RuntimeException('Only the member can sign as the member.');
            }
            $appraisal->fill([
                'member_signed_at' => now(),
                'member_signed_by' => $actor->id,
            ])->save();
        } elseif ($as === 'manager') {
            $isManager = (int) $actor->id === (int) $appraisal->manager_id
                || (int) $actor->id === (int) $appraisal->user?->manager_id;
            if (! $isManager && ! $actor->can(HrPermissions::ACCESS)) {
                throw new RuntimeException('Only the manager can sign as the manager.');
            }
            $appraisal->fill([
                'manager_signed_at' => now(),
                'manager_signed_by' => $actor->id,
            ])->save();
        } else {
            throw new RuntimeException('Unknown sign-off role.');
        }

        return $appraisal->refresh();
    }

    public function completeAndLock(Appraisal $appraisal, User $actor): Appraisal
    {
        $this->assertEditable($appraisal, $actor);

        if ($appraisal->self_review === null || $appraisal->self_review === ''
            || $appraisal->manager_review === null || $appraisal->manager_review === '') {
            throw new RuntimeException('Both self and manager reviews are needed before locking.');
        }

        if (! $appraisal->bothSigned()) {
            throw new RuntimeException('Both the member and the manager must sign off before locking.');
        }

        $appraisal->fill([
            'status' => Appraisal::STATUS_LOCKED,
            'locked_at' => now(),
        ])->save();

        return $appraisal->refresh();
    }

    private function assertEditable(Appraisal $appraisal, User $actor): void
    {
        if ($appraisal->isLocked()) {
            throw new RuntimeException('This appraisal is locked and cannot be changed.');
        }

        if (! $appraisal->canBeViewedBy($actor)) {
            throw new RuntimeException('You cannot change this appraisal.');
        }
    }
}
