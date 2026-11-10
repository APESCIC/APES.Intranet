<?php

namespace Modules\Operations\Services;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Operations\Models\ChecklistRun;
use Modules\Operations\Models\ChecklistTemplate;
use Modules\Operations\Models\RotaWeek;
use Modules\Operations\Models\Shift;
use Modules\Operations\Support\Notifier;
use Modules\Operations\Support\TeamAccess;
use Modules\Operations\Support\Uploads;
use RuntimeException;

final class Checklists
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Attach each matching checklist to the week's shifts. Safe to run again.
     */
    public function scheduleWeek(RotaWeek $week): int
    {
        $templates = ChecklistTemplate::query()->where('site_id', $week->site_id)->where('active', true)->get();
        $created = 0;

        foreach ($week->shifts()->get() as $shift) {
            foreach ($templates as $template) {
                if ($template->appliesTo($shift) && $this->attach($template, $shift)) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * Attach a new template to shifts already published from today on.
     */
    public function scheduleTemplate(ChecklistTemplate $template): int
    {
        $shifts = Shift::query()
            ->published()
            ->where('site_id', $template->site_id)
            ->where('ends_at', '>=', now())
            ->get();

        return $shifts->filter(fn (Shift $shift) => $template->appliesTo($shift) && $this->attach($template, $shift))->count();
    }

    /**
     * @param  array<int, array{done?: mixed, note?: ?string}>  $items
     * @param  array<int, UploadedFile>  $photos
     */
    public function save(ChecklistRun $run, array $items, array $photos, User $member, bool $complete): void
    {
        if ($run->isComplete()) {
            throw new RuntimeException('This checklist is complete and can no longer be changed.');
        }

        if ($complete) {
            $unexplained = $run->items->filter(fn ($item) => empty($items[$item->id]['done']) && blank($items[$item->id]['note'] ?? null));
            if ($unexplained->isNotEmpty()) {
                throw new RuntimeException('Tick every check, or add a note saying why it was not done: '.$unexplained->pluck('label')->implode(', ').'.');
            }
        }

        DB::transaction(function () use ($run, $items, $photos, $member, $complete) {
            foreach ($run->items as $item) {
                $input = $items[$item->id] ?? [];
                $item->done = ! empty($input['done']);
                $item->note = filled($input['note'] ?? null) ? (string) $input['note'] : null;

                if (isset($photos[$item->id])) {
                    $item->photo_path = Uploads::storePhoto($photos[$item->id], 'operations/checklists/'.$run->id);
                }

                $item->save();
            }

            if ($complete) {
                $run->forceFill(['completed_at' => now(), 'completed_by' => $member->getKey()])->save();
                $this->audit->record('operations.checklist_completed', $run, [
                    'done' => $run->items->where('done', true)->count(),
                    'not_done' => $run->items->where('done', false)->count(),
                ], $member);
            }
        });
    }

    /**
     * Tell the managers about checklists not completed by their due time.
     */
    public function alertMissed(): int
    {
        $runs = ChecklistRun::query()
            ->with(['template', 'shift.member', 'site'])
            ->whereNull('completed_at')
            ->whereNull('missed_alerted_at')
            ->where('due_at', '<=', now())
            ->whereHas('shift', fn (Builder $shift) => $shift->published())
            ->get();

        foreach ($runs as $run) {
            $run->forceFill(['missed_alerted_at' => now()])->save();
            $this->audit->record('operations.checklist_missed', $run, ['site_id' => $run->site_id]);

            Notifier::send(
                TeamAccess::approversFor($run->site->team, $run->shift->member),
                'Missed checklist: '.$run->template->name.' at '.$run->site->name,
                [
                    '"'.$run->template->name.'" was due by '.$run->due_at->format('D j M H:i').' on '.$run->shift->label()
                        .' ('.($run->shift->member->name ?? 'open shift').') and has not been completed.',
                ],
                route('operations.checklists.runs.show', $run),
            );
        }

        return $runs->count();
    }

    private function attach(ChecklistTemplate $template, Shift $shift): bool
    {
        if (ChecklistRun::query()->where('checklist_template_id', $template->id)->where('shift_id', $shift->id)->exists()) {
            return false;
        }

        $run = ChecklistRun::query()->create([
            'checklist_template_id' => $template->id,
            'shift_id' => $shift->id,
            'site_id' => $shift->site_id,
            'due_at' => $this->dueAt($template, $shift),
        ]);

        foreach (array_values($template->items) as $position => $label) {
            $run->items()->create(['label' => $label, 'sort_order' => $position]);
        }

        return true;
    }

    /**
     * Due at the set time on the shift's day, or by the end of the shift if
     * that time falls before the shift starts.
     */
    private function dueAt(ChecklistTemplate $template, Shift $shift): CarbonImmutable
    {
        $due = $shift->starts_at->setTimeFromTimeString($template->due_time);

        return $due->lessThan($shift->starts_at) ? $shift->ends_at : $due;
    }
}
