<?php

namespace Modules\Hr\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Hr\Services\RecognitionService;
use RuntimeException;

class RecognitionController
{
    public function create(Request $request): View
    {
        return view('hr::recognition.create', [
            'members' => User::query()->active()->orderBy('name')->limit(300)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, RecognitionService $recognitions, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'message' => ['required', 'string', 'max:1000'],
            'post_to_feed' => ['sometimes', 'boolean'],
        ]);

        $recipient = User::query()->findOrFail($data['recipient_id']);

        try {
            $recognition = $recognitions->give(
                $request->user(),
                $recipient,
                $data['message'],
                (bool) ($data['post_to_feed'] ?? false),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['recipient_id' => $e->getMessage()])->withInput();
        }

        $audit->record('hr.recognition.given', $recognition, [
            'recipient_id' => $recipient->id,
            'posted_to_feed' => $recognition->posted_to_feed,
        ], $request->user());

        return redirect()->route('people.profile.show', $recipient)
            ->with('status', 'Recognition given'.($recognition->posted_to_feed ? ' and posted to the feed' : '').'.');
    }
}
