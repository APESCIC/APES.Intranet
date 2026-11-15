<?php

namespace App\Core\Modules\Http\Controllers;

use App\Core\Modules\InvalidModuleSettingException;
use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\SettingType;
use App\Core\System\CloudronAddons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class AdminModulesController
{
    public function index(ModuleRegistry $modules): View
    {
        $rows = $modules->manifests()->map(function ($manifest) use ($modules) {
            $missing = $modules->missingCloudronAddons($manifest->slug);

            return [
                'manifest' => $manifest,
                'enabled' => $modules->enabled($manifest->slug),
                'settings' => $modules->settings($manifest->slug),
                'missing_addons' => collect($missing)->map(
                    fn (string $key) => CloudronAddons::definitions()[$key]['label'] ?? $key,
                )->all(),
                'enabled_dependents' => $modules->enabledDependents($manifest->slug),
            ];
        });

        return view('core.admin.modules.index', [
            'rows' => $rows,
        ]);
    }

    public function updateState(Request $request, string $slug, ModuleRegistry $modules): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            if ($data['enabled']) {
                $modules->enable($slug, $data['reason'], $request->user());
            } else {
                $modules->disable($slug, $data['reason'], $request->user());
            }
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.modules.index')
                ->with('error', $exception->getMessage())
                ->withFragment('module-'.$slug);
        }

        $verb = $data['enabled'] ? 'enabled' : 'disabled';

        return redirect()
            ->route('admin.modules.index')
            ->with('status', "Module {$slug} {$verb}.")
            ->withFragment('module-'.$slug);
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
            return redirect()
                ->route('admin.modules.index')
                ->with('error', $exception->getMessage())
                ->withFragment('module-'.$slug);
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('status', "Settings for {$slug} saved.")
            ->withFragment('module-'.$slug);
    }
}
