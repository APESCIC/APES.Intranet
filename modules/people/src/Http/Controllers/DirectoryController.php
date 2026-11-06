<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\People\Support\ProfileFields;

class DirectoryController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');
        $team = trim((string) $request->query('team', ''));
        $skill = trim((string) $request->query('skill', ''));

        $people = User::query()
            ->directoryVisible()
            ->with('roles')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('job_title', 'like', $like)
                        ->orWhere('team', 'like', $like);
                });
            })
            ->when($team !== '', fn ($query) => $query->where('team', $team))
            ->when($skill !== '', fn ($query) => $query->whereJsonContains('skills', $skill))
            ->when($type !== '' && RoleName::tryFrom($type)?->isAccountType(), function ($query) use ($type) {
                $query->role($type);
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $teams = User::query()->directoryVisible()->whereNotNull('team')->distinct()->orderBy('team')->pluck('team');

        return view('people::directory.index', [
            'people' => $people,
            'q' => $q,
            'type' => $type,
            'team' => $team,
            'skill' => $skill,
            'teams' => $teams,
            'accountTypes' => RoleName::accountTypes(),
        ]);
    }

    public function show(Request $request, User $user): View
    {
        abort_unless($request->user()?->can('view', $user), 403);

        return view('people::directory.show', [
            'person' => $user->load(['manager', 'roles']),
            'visible' => ProfileFields::visibleTo($request->user(), $user),
        ]);
    }
}
