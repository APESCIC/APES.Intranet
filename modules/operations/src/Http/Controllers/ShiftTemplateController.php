<?php

namespace Modules\Operations\Http\Controllers;

use App\Core\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Operations\Models\ShiftTemplate;
use Modules\Operations\Models\Site;
use Modules\Operations\OperationsPermissions;
use Modules\Operations\Support\TeamAccess;

class ShiftTemplateController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->can(OperationsPermissions::ROTA_MANAGE), 403);
        $sites = TeamAccess::manageableSites($request->user(), OperationsPermissions::ROTA_MANAGE);

        return view('operations::rota.templates', [
            'sites' => $sites,
            'templates' => ShiftTemplate::query()
                ->with('site')
                ->whereIn('site_id', $sites->pluck('id'))
                ->orderBy('site_id')->orderBy('weekday')->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'site_id' => ['required', 'integer', Rule::exists('operations_sites', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'string', 'max:120'],
            'weekdays' => ['required', 'array', 'min:1'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'different:starts_at'],
        ]);

        $site = Site::query()->findOrFail($data['site_id']);
        abort_unless(TeamAccess::canManageSite($request->user(), $site, OperationsPermissions::ROTA_MANAGE), 403);

        foreach (array_unique($data['weekdays']) as $weekday) {
            $template = ShiftTemplate::query()->create([
                'site_id' => $site->id,
                'name' => $data['name'],
                'role' => $data['role'],
                'weekday' => (int) $weekday,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'active' => true,
            ]);
            $audit->record('operations.shift_template_created', $template, ['site_id' => $site->id]);
        }

        return back()->with('status', 'Shift template saved.');
    }

    public function destroy(Request $request, ShiftTemplate $template, AuditLogger $audit): RedirectResponse
    {
        abort_unless(TeamAccess::canManageSite($request->user(), $template->site, OperationsPermissions::ROTA_MANAGE), 403);

        $template->update(['active' => false]);
        $audit->record('operations.shift_template_retired', $template, ['site_id' => $template->site_id]);

        return back()->with('status', 'Shift template retired. Existing rotas keep their shifts.');
    }
}
