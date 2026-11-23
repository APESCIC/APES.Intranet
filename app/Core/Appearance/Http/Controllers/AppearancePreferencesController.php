<?php

namespace App\Core\Appearance\Http\Controllers;

use App\Core\Appearance\AppearanceMode;
use App\Core\Appearance\AppearanceTheme;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppearancePreferencesController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'in:'.implode(',', array_column(AppearanceTheme::cases(), 'value'))],
            'mode' => ['required', 'string', 'in:'.implode(',', array_column(AppearanceMode::cases(), 'value'))],
            'reducedTransparency' => ['sometimes', 'boolean'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->fill([
            'appearance_theme' => $validated['theme'],
            'appearance_mode' => $validated['mode'],
            'appearance_reduced_transparency' => $request->boolean('reducedTransparency'),
        ])->save();

        return response()->json([
            'theme' => $user->appearance_theme,
            'mode' => $user->appearance_mode,
            'reducedTransparency' => (bool) $user->appearance_reduced_transparency,
        ]);
    }
}
