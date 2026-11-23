<?php

namespace App\Core\Appearance;

use App\Models\User;
use Illuminate\Http\Request;

class AppearancePreferences
{
    public const STORAGE_KEY = 'apes.intranet.appearance';

    /**
     * @return array{theme: string, mode: string, reducedTransparency: bool}
     */
    public static function defaults(): array
    {
        return [
            'theme' => AppearanceTheme::Glass->value,
            'mode' => AppearanceMode::System->value,
            'reducedTransparency' => false,
        ];
    }

    /**
     * @return array{theme: string, mode: string, reducedTransparency: bool}
     */
    public static function forUser(User $user): array
    {
        return [
            'theme' => self::normaliseTheme($user->appearance_theme),
            'mode' => self::normaliseMode($user->appearance_mode),
            'reducedTransparency' => (bool) $user->appearance_reduced_transparency,
        ];
    }

    public static function resolvedMode(string $mode, ?Request $request = null): string
    {
        if ($mode !== AppearanceMode::System->value) {
            return self::normaliseMode($mode);
        }

        $request ??= request();

        if ($request instanceof Request && self::requestPrefersDark($request)) {
            return AppearanceMode::Dark->value;
        }

        return AppearanceMode::Light->value;
    }

    public static function requestPrefersDark(Request $request): bool
    {
        $preference = strtolower((string) $request->header('Sec-CH-Prefers-Color-Scheme', ''));

        if ($preference === 'dark') {
            return true;
        }

        if ($preference === 'light') {
            return false;
        }

        return false;
    }

    public static function normaliseTheme(?string $theme): string
    {
        return AppearanceTheme::tryFrom((string) $theme)?->value ?? AppearanceTheme::Glass->value;
    }

    public static function normaliseMode(?string $mode): string
    {
        return AppearanceMode::tryFrom((string) $mode)?->value ?? AppearanceMode::System->value;
    }
}
