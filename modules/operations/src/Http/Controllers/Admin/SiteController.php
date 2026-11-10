<?php

namespace Modules\Operations\Http\Controllers\Admin;

use App\Core\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Operations\Models\Site;

class SiteController
{
    public function index(): View
    {
        return view('operations::admin.sites.index', [
            'sites' => Site::query()->orderBy('name')->get(),
            'teams' => User::query()->whereNotNull('team')->distinct()->orderBy('team')->pluck('team'),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $site = Site::query()->create($this->validated($request) + ['active' => true]);
        $audit->record('operations.site_created', $site, ['name' => $site->name]);

        return back()->with('status', 'Site added.');
    }

    public function update(Request $request, Site $site, AuditLogger $audit): RedirectResponse
    {
        $site->update($this->validated($request) + ['active' => $request->boolean('active')]);
        $audit->record('operations.site_updated', $site, ['name' => $site->name, 'active' => $site->active]);

        return back()->with('status', 'Site saved.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'team' => ['nullable', 'string', 'max:120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'radius_metres' => ['nullable', 'integer', 'min:25', 'max:5000'],
        ]);
    }
}
