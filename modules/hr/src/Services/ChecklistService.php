<?php

namespace Modules\Hr\Services;

use App\Core\Helpdesk\Contracts\RaiseItTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Hr\Models\ChecklistTemplate;
use Modules\Hr\Models\ChecklistTemplateItem;
use Modules\Hr\Models\MemberChecklist;
use Modules\Hr\Models\MemberChecklistItem;
use RuntimeException;

final class ChecklistService
{
    public function __construct(private readonly RaiseItTicket $itTickets) {}

    public function ensureDefaults(): void
    {
        foreach (ChecklistTemplate::MEMBER_TYPES as $type) {
            $this->seedTemplate(ChecklistTemplate::KIND_ONBOARDING, $type, ucfirst($type).' onboarding', [
                [ChecklistTemplateItem::ROLE_HR, 'Add HR record and emergency contact'],
                [ChecklistTemplateItem::ROLE_MANAGER, 'Assign buddy and first-week plan'],
                [ChecklistTemplateItem::ROLE_IT, 'Create accounts and equipment'],
            ]);
            $this->seedTemplate(ChecklistTemplate::KIND_OFFBOARDING, $type, ucfirst($type).' offboarding', [
                [ChecklistTemplateItem::ROLE_HR, 'Collect equipment and close HR record'],
                [ChecklistTemplateItem::ROLE_MANAGER, 'Handover work and exit notes'],
                [ChecklistTemplateItem::ROLE_IT, 'Remove accounts (IT ticket raised)'],
            ]);
        }
    }

    public function start(User $member, string $kind, string $memberType, User $actor): MemberChecklist
    {
        $this->ensureDefaults();

        if (! in_array($memberType, ChecklistTemplate::MEMBER_TYPES, true)) {
            throw new RuntimeException('Unknown member type.');
        }

        if (! in_array($kind, [ChecklistTemplate::KIND_ONBOARDING, ChecklistTemplate::KIND_OFFBOARDING], true)) {
            throw new RuntimeException('Unknown checklist kind.');
        }

        $open = MemberChecklist::query()
            ->where('user_id', $member->id)
            ->where('kind', $kind)
            ->where('status', MemberChecklist::STATUS_OPEN)
            ->exists();
        if ($open) {
            throw new RuntimeException('An open '.$kind.' checklist already exists for this member.');
        }

        $template = ChecklistTemplate::query()
            ->where('kind', $kind)
            ->where('member_type', $memberType)
            ->where('active', true)
            ->with('items')
            ->firstOrFail();

        return DB::transaction(function () use ($member, $kind, $memberType, $actor, $template) {
            $checklist = MemberChecklist::query()->create([
                'user_id' => $member->id,
                'template_id' => $template->id,
                'kind' => $kind,
                'member_type' => $memberType,
                'status' => MemberChecklist::STATUS_OPEN,
                'started_by' => $actor->id,
            ]);

            foreach ($template->items as $item) {
                MemberChecklistItem::query()->create([
                    'checklist_id' => $checklist->id,
                    'owner_role' => $item->owner_role,
                    'title' => $item->title,
                    'sort_order' => $item->sort_order,
                ]);
            }

            if ($kind === ChecklistTemplate::KIND_OFFBOARDING) {
                $ticketId = $this->itTickets->raise(
                    $actor,
                    'Account removal request: '.$member->name,
                    '<p>Please remove intranet and related accounts for <strong>'.e($member->name).'</strong> ('.$member->email.'). Offboarding checklist #'.$checklist->id.'.</p><p>Fake demo data only — no automatic Cloudron changes.</p>',
                    'sign-in',
                );
                $checklist->forceFill(['it_ticket_id' => $ticketId])->save();
            }

            return $checklist->refresh()->load('items', 'user');
        });
    }

    public function completeItem(MemberChecklistItem $item, User $actor): MemberChecklistItem
    {
        if ($item->checklist->status !== MemberChecklist::STATUS_OPEN) {
            throw new RuntimeException('This checklist is already completed.');
        }

        $item->forceFill([
            'completed_at' => now(),
            'completed_by' => $actor->id,
        ])->save();

        $checklist = $item->checklist->load('items');
        if ($checklist->items->every(fn (MemberChecklistItem $row) => $row->isComplete())) {
            $checklist->forceFill([
                'status' => MemberChecklist::STATUS_COMPLETED,
                'completed_at' => now(),
            ])->save();
        }

        return $item->refresh();
    }

    /**
     * @param  list<array{0: string, 1: string}>  $items
     */
    private function seedTemplate(string $kind, string $memberType, string $name, array $items): void
    {
        $template = ChecklistTemplate::query()->firstOrCreate(
            ['kind' => $kind, 'member_type' => $memberType],
            ['name' => $name, 'active' => true],
        );

        if ($template->items()->exists()) {
            return;
        }

        foreach ($items as $index => [$role, $title]) {
            ChecklistTemplateItem::query()->create([
                'template_id' => $template->id,
                'owner_role' => $role,
                'title' => $title,
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }
}
