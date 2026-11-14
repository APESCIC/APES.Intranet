<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Core\Access\SensitiveData;
use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Hr\Models\HrRecord;

class HrRecordController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::HR_ACCESS), 403);

        $q = trim((string) $request->query('q', ''));

        $people = User::query()
            ->with(['hrRecord', 'roles'])
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('job_title', 'like', $like);
                });
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('hr::records.index', [
            'people' => $people,
            'q' => $q,
        ]);
    }

    public function show(Request $request, User $user, SensitiveData $sensitive): View
    {
        $record = HrRecord::query()->firstOrCreate(['user_id' => $user->id]);
        $sensitive->openHrRecord($record);

        return view('hr::records.show', [
            'person' => $user->load('roles'),
            'record' => $record,
            'canEdit' => $this->canEdit($request->user()),
            'fields' => config('hr.editable_fields'),
        ]);
    }

    public function update(Request $request, User $user, SensitiveData $sensitive, AuditLogger $audit): RedirectResponse
    {
        abort_unless($this->canEdit($request->user()), 403);

        $record = HrRecord::query()->firstOrCreate(['user_id' => $user->id]);
        $sensitive->openHrRecord($record);

        $fields = config('hr.editable_fields');
        $data = $request->validate([
            'employment_status' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'contract_type' => ['nullable', 'string', 'max:100'],
            'work_location' => ['nullable', 'string', 'max:150'],
            'emergency_contact_name' => ['nullable', 'string', 'max:150'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $record->fill(array_intersect_key($data, array_flip($fields)));
        $changed = array_keys($record->getDirty());
        $record->save();

        $audit->record('hr.record.updated', $record, [
            'fields' => $changed,
            'member_id' => $user->id,
        ]);

        return redirect()
            ->route('hr.records.show', $user)
            ->with('status', 'HR record saved.');
    }

    private function canEdit(?User $actor): bool
    {
        if ($actor === null) {
            return false;
        }

        return $actor->hasAnyRole([RoleName::Hr->value, RoleName::Admin->value]);
    }
}
