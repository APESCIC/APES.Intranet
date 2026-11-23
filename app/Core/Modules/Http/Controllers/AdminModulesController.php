<?php

namespace App\Core\Modules\Http\Controllers;

use App\Core\Modules\InvalidModuleSettingException;
use App\Core\Modules\ModuleManifest;
use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\SettingType;
use App\Core\System\CloudronAddons;
use App\Core\Version\AppVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use InvalidArgumentException;

final class AdminModulesController
{
    public function index(ModuleRegistry $modules, AppVersion $appVersion): View
    {
        $rows = $modules->manifests()
            ->sortBy(fn (ModuleManifest $manifest) => $manifest->name)
            ->map(fn (ModuleManifest $manifest) => $this->row($modules, $manifest))
            ->values();

        return view('core.admin.modules.index', [
            'coreVersion' => $appVersion->current(),
            'rows' => $rows,
        ]);
    }

    public function show(string $slug, ModuleRegistry $modules, AppVersion $appVersion): View
    {
        $manifest = $modules->manifest($slug);
        if ($manifest === null) {
            abort(404);
        }

        return view('core.admin.modules.show', [
            'row' => $this->row($modules, $manifest),
            'coreVersion' => $appVersion->current(),
        ]);
    }

    public function updateState(Request $request, string $slug, ModuleRegistry $modules): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:500'],
            'return' => ['nullable', 'string', 'in:show'],
        ]);

        try {
            if ($data['enabled']) {
                $modules->enable($slug, $data['reason'], $request->user());
            } else {
                $modules->disable($slug, $data['reason'], $request->user());
            }
        } catch (InvalidArgumentException $exception) {
            return $this->redirectFor($request, $slug)
                ->with('error', $exception->getMessage());
        }

        $verb = $data['enabled'] ? 'enabled' : 'disabled';

        return $this->redirectFor($request, $slug)
            ->with('status', "Module {$slug} {$verb}.");
    }

    public function updateSettings(Request $request, string $slug, ModuleRegistry $modules): RedirectResponse
    {
        $manifest = $modules->manifest($slug);
        if ($manifest === null) {
            abort(404);
        }

        $values = [];
        foreach ($manifest->settings as $key => $definition) {
            $raw = $request->input('settings.'.$key);

            if ($definition->type === SettingType::SecretRef) {
                $values[$key] = [
                    'env' => $request->input("settings.{$key}.env"),
                    'vault' => $request->input("settings.{$key}.vault"),
                ];

                continue;
            }

            if ($definition->type === SettingType::Bool) {
                $values[$key] = $request->boolean("settings.{$key}");

                continue;
            }

            $values[$key] = $raw;
        }

        try {
            $modules->setSettings($slug, $values);
        } catch (InvalidModuleSettingException $exception) {
            return $this->redirectFor($request, $slug)
                ->with('error', $exception->getMessage());
        }

        return $this->redirectFor($request, $slug)
            ->with('status', "Settings for {$slug} saved.");
    }

    /**
     * @return array{
     *     manifest: ModuleManifest,
     *     enabled: bool,
     *     settings: array<string, mixed>,
     *     missing_addons: list<string>,
     *     missing_addon_labels: list<string>,
     *     dependencies: Collection<int, ModuleManifest>,
     *     dependents: Collection<int, ModuleManifest>,
     *     enabled_dependents: Collection<int, ModuleManifest>,
     * }
     */
    private function row(ModuleRegistry $modules, ModuleManifest $manifest): array
    {
        $missing = $modules->missingCloudronAddons($manifest->slug);

        return [
            'manifest' => $manifest,
            'enabled' => $modules->enabled($manifest->slug),
            'settings' => $modules->settings($manifest->slug),
            'missing_addons' => $missing,
            'missing_addon_labels' => collect($missing)->map(
                fn (string $key) => CloudronAddons::definitions()[$key]['label'] ?? $key,
            )->all(),
            'dependencies' => $modules->dependencies($manifest->slug),
            'dependents' => $modules->dependents($manifest->slug),
            'enabled_dependents' => $modules->enabledDependents($manifest->slug),
        ];
    }

    private function redirectFor(Request $request, string $slug): RedirectResponse
    {
        if ($request->input('return') === 'show') {
            return redirect()->route('admin.modules.show', $slug);
        }

        return redirect()
            ->route('admin.modules.index')
            ->withFragment('module-'.$slug);
    }
}
