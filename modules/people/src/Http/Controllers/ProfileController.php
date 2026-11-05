<?php

namespace Modules\People\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\People\Http\Requests\UpdateProfileRequest;
use Modules\People\Support\ProfileFields;

class ProfileController
{
    public function show(Request $request, User $user): View
    {
        $this->authorizeView($request->user(), $user);

        return view('people::profile.show', [
            'person' => $user->load('manager'),
            'visible' => ProfileFields::visibleTo($request->user(), $user),
            'canEdit' => $request->user()->can('update', $user),
        ]);
    }

    public function edit(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->can('update', $user), 403);

        return view('people::profile.edit', [
            'person' => $user,
            'editable' => ProfileFields::editableBy($user, $user),
        ]);
    }

    public function update(UpdateProfileRequest $request, AuditLogger $audit): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->can('update', $user), 403);

        $data = $request->safe()->except(['photo', 'skills']);
        $skills = $request->skillsList();

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('profile-photos', 'public');
        }

        $data['skills'] = $skills;
        $user->fill($data)->save();

        $audit->record('people.profile_updated', $user, ['fields' => array_keys($data)]);

        return redirect()->route('people.profile.show', $user)->with('status', 'Your profile has been saved.');
    }

    private function authorizeView(?User $viewer, User $subject): void
    {
        abort_unless($viewer !== null && $viewer->can('view', $subject), 403);
    }
}
