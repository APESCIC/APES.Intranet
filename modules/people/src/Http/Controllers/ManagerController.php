<?php

namespace Modules\People\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\People\Support\ManagerGraph;

class ManagerController
{
    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can('setManager', $user), 403);

        $data = $request->validate([
            'manager_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $manager = isset($data['manager_id']) ? User::query()->find($data['manager_id']) : null;

        try {
            ManagerGraph::assertCanReportTo($user, $manager);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['manager_id' => $exception->getMessage()]);
        }

        $before = $user->manager_id;
        $user->manager()->associate($manager);
        $user->save();

        $audit->record('people.manager_changed', $user, [
            'before' => $before,
            'after' => $user->manager_id,
        ]);

        return back()->with('status', 'Manager updated.');
    }
}
