<?php

namespace Modules\People\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\People\Support\ProfileFields;

class PeoplePickerController
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $q = trim((string) $request->query('q', ''));
        abort_if(mb_strlen($q) < 1, 422, 'Enter a name or email to search.');

        $started = hrtime(true);

        $people = User::query()
            ->directoryVisible()
            ->where(function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where('name', 'like', $like)->orWhere('email', 'like', $like);
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $viewer = $request->user();
        $payload = $people->map(function (User $person) use ($viewer) {
            $visible = ProfileFields::visibleTo($viewer, $person);

            return [
                'id' => $person->id,
                'name' => in_array('name', $visible, true) ? $person->name : null,
                'email' => in_array('email', $visible, true) ? $person->email : null,
                'team' => in_array('team', $visible, true) ? $person->team : null,
                'job_title' => in_array('job_title', $visible, true) ? $person->job_title : null,
            ];
        });

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        return response()->json([
            'data' => $payload,
            'meta' => ['took_ms' => round($elapsedMs, 2)],
        ]);
    }
}
