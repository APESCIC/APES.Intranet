@props(['code', 'title'])
{{-- No database, session or menu here, so the page still renders when those are down. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#d2f4fa">
        <meta name="robots" content="noindex">
        <title>{{ $title }} - {{ config('app.name') }}</title>
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('brand/apes-favicon-32.png') }}">
        @vite(['resources/css/app.css'])
    </head>
    <body class="workspace-shell">
        <a class="skip-link" href="#main-content">Skip to main content</a>
        <div class="sg-content">
            <header class="sg-card sg-topbar">
                <x-shell.brand />
            </header>
            <main id="main-content" tabindex="-1" class="sg-card max-w-xl p-6">
                <p class="text-sm font-semibold uppercase tracking-wide text-sg-muted">Error {{ $code }}</p>
                <h1 class="sg-page-header__title mt-1">{{ $title }}</h1>
                <div class="mt-3 space-y-3">
                    {{ $slot }}
                </div>
            </main>
            <footer class="site-footer">
                <span>&copy; {{ now()->year }} Association of Protecting Exotic Species CIC</span>
            </footer>
        </div>
    </body>
</html>
