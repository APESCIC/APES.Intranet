@props(['title' => null, 'subtitle' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#d2f4fa">
        <title>{{ $title ? $title.' - ' : '' }}{{ config('app.name') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/apes-favicon-32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="apple-mobile-web-app-title" content="Intranet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="workspace-shell">
        <a class="skip-link" href="#main-content">Skip to main content</a>

        <div class="sg-page" data-workspace-page>
            @if ($shellUser)
                <aside class="sg-rail-dock" aria-label="Main menu" data-workspace-rail-dock>
                    <x-shell.rail
                        :menu="$menu"
                        :user="$shellUser"
                        :qa-enabled="$qaEnabled"
                        :show-menu-mode-switcher="$showMenuModeSwitcher ?? false"
                        :menu-mode="$menuMode ?? null"
                    />
                </aside>
            @endif

            <div @class(['sg-main-column', 'sg-main-column--rail' => $shellUser])>
                <div class="sg-content">
                    <x-shell.topbar :has-menu="(bool) $shellUser" />

                    @if ($title)
                        <header class="sg-page-header">
                            <h1 class="sg-page-header__title">{{ $title }}</h1>
                            @if ($subtitle)
                                <p class="sg-page-header__subtitle">{{ $subtitle }}</p>
                            @endif
                        </header>
                    @endif

                    <main id="main-content" class="app-main" tabindex="-1">
                        {{ $slot }}
                    </main>

                    <footer class="site-footer">
                        <span>&copy; {{ now()->year }} Association of Protecting Exotic Species CIC</span>
                        @if ($footerVersion)
                            <a class="site-footer__version" href="{{ $footerVersion['url'] }}" data-app-version><span class="sr-only">{{ $footerVersion['hiddenBefore'] }}</span>{{ $footerVersion['label'] }}@if ($footerVersion['hiddenAfter'])<span class="sr-only">{{ $footerVersion['hiddenAfter'] }}</span>@endif</a>
                        @endif
                        <nav class="site-footer__links" aria-label="Legal">
                            <a href="{{ route('privacy') }}" @if (request()->routeIs('privacy')) aria-current="page" @endif>Privacy notice</a>
                        </nav>
                    </footer>
                </div>
            </div>
        </div>

        @if ($shellUser)
            <x-shell.drawer />
        @endif
    </body>
</html>
