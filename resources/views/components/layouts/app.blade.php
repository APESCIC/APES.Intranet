@props(['title' => null, 'subtitle' => null])
@php
    use App\Core\Appearance\AppearancePreferences;
    use App\Core\Navigation\MenuMode;

    $appearanceBoot = $appearanceServer ?? AppearancePreferences::defaults();
    $resolvedAppearanceMode = AppearancePreferences::resolvedMode($appearanceBoot['mode']);
    $isAdminPanel = ($shellPanel ?? null) === MenuMode::Admin;
    $adminBreadcrumbs = [];
    if ($isAdminPanel) {
        $onOverview = ($adminPage?->routeName ?? null) === 'admin.overview';
        $adminBreadcrumbs[] = $onOverview
            ? ['label' => 'Admin panel', 'current' => true]
            : ['label' => 'Admin panel', 'url' => route('admin.overview')];
        if ($adminSection !== null && ($adminSection->key ?? null) !== 'home') {
            $adminBreadcrumbs[] = ['label' => $adminSection->heading];
        }
        if ($adminPage !== null && ! $onOverview) {
            $adminBreadcrumbs[] = ['label' => $adminPage->label, 'current' => true];
        }
    }
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    data-theme="{{ $appearanceBoot['theme'] }}"
    data-mode="{{ $resolvedAppearanceMode }}"
    data-appearance-mode="{{ $appearanceBoot['mode'] }}"
    @if ($appearanceBoot['reducedTransparency']) data-reduced-transparency @endif
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#c8eaf1">
        <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/apes-favicon-32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="apple-mobile-web-app-title" content="Intranet">
        <x-appearance.boot :appearance-server="$appearanceServer ?? null" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        class="workspace-shell"
        data-appearance-root
        @auth data-appearance-save-url="{{ route('appearance.preferences.update') }}" @endauth
    >
        <a class="skip-link" href="#main-content">Skip to main content</a>

        <div class="sg-page" data-workspace-page>
            <div class="sg-main-column">
                <div class="sg-content">
                    <x-shell.topbar
                        :has-menu="(bool) $shellUser"
                        :menu="$menu ?? []"
                        :panel="$shellPanel ?? null"
                        :user="$shellUser"
                        :qa-profile="$qaProfile"
                        :qa-picker="$qaPicker"
                        :qa-return-to="$qaReturnTo"
                        :qa-confirm="$qaConfirm"
                        :unread-notifications="$unreadNotifications ?? 0"
                        :recent-notifications="$recentNotifications ?? collect()"
                    />

                    @if ($title || $isAdminPanel)
                        <header class="sg-page-header">
                            @if ($isAdminPanel)
                                <x-shell.breadcrumbs :items="$adminBreadcrumbs" />
                            @endif
                            @if ($title)
                                <h1 class="sg-page-header__title">{{ $title }}</h1>
                                @if ($subtitle)
                                    <p class="sg-page-header__subtitle">{{ $subtitle }}</p>
                                @endif
                            @endif
                            @if ($isAdminPanel && $adminSection !== null)
                                <x-shell.admin-sub-nav :group="$adminSection" />
                            @endif
                        </header>
                    @endif

                    <main id="main-content" class="app-main" tabindex="-1">
                        @if ($qaProfile)
                            <x-qa.notices :profile="$qaProfile" :confirm="$qaConfirm" :notice="$qaNotice" />
                        @endif
                        {{ $slot }}
                    </main>

                    <x-shell.site-footer :footer-version="$footerVersion" />
                </div>
            </div>
        </div>
    </body>
</html>