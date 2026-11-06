<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\People\Models\Placement;
use Modules\People\Models\PlacementHour;

class StudentPlacementController
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->can(AccessMatrix::STUDENT_AREA)
            || $user?->can(AccessMatrix::TEAM_SUPERVISE)
            || $user?->can(AccessMatrix::HR_ACCESS), 403);

        $placements = Placement::query()
            ->with(['student', 'supervisor'])
            ->when(
                $user->can(AccessMatrix::STUDENT_AREA) && ! $user->can(AccessMatrix::HR_ACCESS) && ! $user->can(AccessMatrix::TEAM_MANAGE),
                fn ($q) => $q->where('student_id', $user->getKey()),
            )
            ->when(
                $user->can(AccessMatrix::TEAM_SUPERVISE) && ! $user->can(AccessMatrix::HR_ACCESS) && ! $user->can(AccessMatrix::TEAM_MANAGE) && ! $user->can(AccessMatrix::STUDENT_AREA),
                fn ($q) => $q->where('supervisor_id', $user->getKey()),
            )
            ->when(
                $user->can(AccessMatrix::TEAM_SUPERVISE) && $user->can(AccessMatrix::STUDENT_AREA) && ! $user->can(AccessMatrix::HR_ACCESS) && ! $user->can(AccessMatrix::TEAM_MANAGE),
                fn ($q) => $q->where(function ($inner) use ($user) {
                    $inner->where('student_id', $user->getKey())->orWhere('supervisor_id', $user->getKey());
                }),
            )
            ->orderByDesc('starts_on')
            ->get();

        return view('people::students.index', compact('placements'));
    }

    public function show(Request $request, Placement $placement): View
    {
        abort_unless($request->user()?->can('view', $placement), 403);

        $placement->load(['student', 'supervisor', 'hours' => fn ($q) => $q->orderByDesc('worked_on')]);

        return view('people::students.show', [
            'placement' => $placement,
            'approvedMinutes' => $placement->approvedMinutes(),
        ]);
    }

    public function storeHours(Request $request, Placement $placement, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can('view', $placement), 403);
        abort_unless((int) $placement->student_id === (int) $request->user()->getKey(), 403);
        abort_unless($request->user()->can('create', PlacementHour::class), 403);

        $data = $request->validate([
            'worked_on' => ['required', 'date'],
            'minutes' => ['required', 'integer', 'min:15', 'max:720'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $hour = $placement->hours()->create([
            ...$data,
            'student_id' => $request->user()->getKey(),
            'status' => PlacementHour::STATUS_PENDING,
        ]);

        $audit->record('people.placement_hours_logged', $hour, [
            'placement_id' => $placement->id,
            'minutes' => $hour->minutes,
        ]);

        return back()->with('status', 'Hours submitted for sign-off.');
    }

    public function reviewHours(Request $request, PlacementHour $hour, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can('review', $hour), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,returned'],
            'reviewer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $hour->forceFill([
            'status' => $data['decision'] === 'approved' ? PlacementHour::STATUS_APPROVED : PlacementHour::STATUS_RETURNED,
            'reviewer_notes' => $data['reviewer_notes'] ?? null,
            'reviewed_by' => $request->user()->getKey(),
            'reviewed_at' => now(),
        ])->save();

        $audit->record('people.placement_hours_reviewed', $hour, [
            'status' => $hour->status,
        ]);

        return back()->with('status', 'Hours '.$hour->status.'.');
    }
}
