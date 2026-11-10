<?php

namespace Modules\Operations\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Operations\Models\Availability;

class AvailabilityController
{
    public function index(Request $request): View
    {
        return view('operations::rota.availability', [
            'periods' => Availability::query()
                ->where('user_id', $request->user()->getKey())
                ->where('ends_at', '>=', now())
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        Availability::query()->create($data + ['user_id' => $request->user()->getKey()]);

        return back()->with('status', 'Unavailability saved. Rota builders will see a clash if you are put on a shift then.');
    }

    public function destroy(Request $request, Availability $availability): RedirectResponse
    {
        abort_unless((int) $availability->user_id === (int) $request->user()->getKey(), 403);

        $availability->delete();

        return back()->with('status', 'Unavailability removed.');
    }
}
