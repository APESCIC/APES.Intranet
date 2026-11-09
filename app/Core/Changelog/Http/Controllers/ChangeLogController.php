<?php

namespace App\Core\Changelog\Http\Controllers;

use App\Core\Changelog\Release;
use App\Core\Version\AppVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The change log hub. Every list, filter and version lookup goes through the
 * release policy, so a note is never sent to someone it is not meant for.
 */
class ChangeLogController
{
    public function index(Request $request, AppVersion $version): View
    {
        $type = $request->query('type');
        $type = is_string($type) && in_array($type, Release::CHANGE_TYPES, true) ? $type : null;

        $releases = $this->visibleReleases($request->user());
        if ($releases !== null && $type !== null) {
            $releases = $releases->filter(fn (Release $release) => in_array($type, $release->changeTypes(), true))->values();
        }

        return view('core.change-log.index', [
            'releases' => $releases,
            'type' => $type,
            'types' => Release::CHANGE_TYPES,
            'current' => $version->current(),
            'seesDrafts' => Gate::allows('viewDrafts', Release::class),
        ]);
    }

    public function show(Request $request, string $version, AppVersion $appVersion): View
    {
        try {
            $release = Release::query()->where('version', $version)->first();
        } catch (QueryException $exception) {
            report($exception);
            abort(503);
        }

        abort_if($release === null || Gate::denies('view', $release), 404);

        return view('core.change-log.show', [
            'release' => $release,
            'current' => $appVersion->current(),
            'seesDrafts' => Gate::allows('viewDrafts', Release::class),
        ]);
    }

    /**
     * @return Collection<int, Release>|null Null when the database cannot be read.
     */
    private function visibleReleases(?User $user): ?Collection
    {
        if ($user === null) {
            return new Collection;
        }

        try {
            $query = Release::query()->newestFirst();
            if (Gate::forUser($user)->denies('viewDrafts', Release::class)) {
                $query->published();
            }

            return $query->get()->filter(fn (Release $release) => $user->can('view', $release))->values();
        } catch (QueryException $exception) {
            report($exception);

            return null;
        }
    }
}
