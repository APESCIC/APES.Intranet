<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Models\MicrosoftLink;
use Modules\Communications\Support\MicrosoftUrlAllowlist;

class MicrosoftLinkController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::MANAGE_M365), 403);

        return view('communications::m365.index', [
            'links' => MicrosoftLink::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::MANAGE_M365), 403);
        $data = $this->validated($request);
        $link = MicrosoftLink::query()->create($data);
        $audit->record('comms.m365_link_created', $link, ['key' => $link->key]);

        return back()->with('status', 'Microsoft 365 link added.');
    }

    public function update(Request $request, MicrosoftLink $link, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::MANAGE_M365), 403);
        $data = $this->validated($request, $link);
        $link->update($data);
        $audit->record('comms.m365_link_updated', $link, ['key' => $link->key]);

        return back()->with('status', 'Microsoft 365 link updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?MicrosoftLink $existing = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60', 'alpha_dash'],
            'name' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url', 'max:500'],
            'icon' => ['required', 'string', 'max:60'],
            'audience' => ['required', 'in:all,staff'],
            'opens_in_new_tab' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        if (! MicrosoftUrlAllowlist::allows($data['url'], config('communications.m365_url_allowlist'))) {
            throw ValidationException::withMessages([
                'url' => 'The URL must be https and on an allowed Microsoft domain.',
            ]);
        }

        $data['opens_in_new_tab'] = (bool) ($data['opens_in_new_tab'] ?? true);
        $data['active'] = (bool) ($data['active'] ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $query = MicrosoftLink::query()->where('key', $data['key']);
        if ($existing) {
            $query->where('id', '!=', $existing->id);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['key' => 'That key is already in use.']);
        }

        return $data;
    }
}
