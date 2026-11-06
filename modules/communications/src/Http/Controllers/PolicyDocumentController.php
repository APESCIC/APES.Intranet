<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Models\PolicyDocument;
use Modules\Communications\Models\PolicyRead;

class PolicyDocumentController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $policies = PolicyDocument::query()->where('active', true)->orderBy('title')->get();
        $reviewDue = $request->user()->can(CommunicationsPermissions::PUBLISH)
            ? PolicyDocument::query()
                ->where('active', true)
                ->whereNotNull('review_due_on')
                ->whereDate('review_due_on', '<=', now()->addDays(30))
                ->orderBy('review_due_on')
                ->get()
            : collect();

        return view('communications::policies.index', [
            'policies' => $policies,
            'reviewDue' => $reviewDue,
            'canManage' => $request->user()->can(CommunicationsPermissions::PUBLISH),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'sharepoint_url' => ['required', 'url', 'starts_with:https://'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'review_due_on' => ['nullable', 'date'],
        ]);

        PolicyDocument::query()->create($data + ['active' => true]);

        return back()->with('status', 'Policy index entry added.');
    }

    public function markRead(Request $request, PolicyDocument $policy): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        PolicyRead::query()->firstOrCreate(
            ['policy_document_id' => $policy->id, 'user_id' => $request->user()->id],
            ['read_at' => now()],
        );

        return back()->with('status', 'Read receipt recorded.');
    }
}
