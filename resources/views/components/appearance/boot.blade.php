@props(['appearanceServer' => null])
<script>
(function () {
    var STORAGE_KEY = @json(\App\Core\Appearance\AppearancePreferences::STORAGE_KEY);
    var defaults = @json(\App\Core\Appearance\AppearancePreferences::defaults());
    var prefs = defaults;
    var server = @json($appearanceServer);
    if (server) {
        prefs = Object.assign({}, defaults, server);
    } else {
        try {
            var stored = localStorage.getItem(STORAGE_KEY);
            if (stored) {
                prefs = Object.assign({}, defaults, JSON.parse(stored));
            }
        } catch (e) {}
    }

    var mode = prefs.mode === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : (prefs.mode === 'dark' ? 'dark' : 'light');
    var theme = prefs.theme === 'jungle' ? 'jungle' : 'glass';

    var html = document.documentElement;
    html.setAttribute('data-theme', theme);
    html.setAttribute('data-mode', mode);
    html.setAttribute('data-appearance-mode', prefs.mode);
    if (prefs.reducedTransparency) {
        html.setAttribute('data-reduced-transparency', '');
    } else {
        html.removeAttribute('data-reduced-transparency');
    }

    var colours = {
        glass: { light: '#c8eaf1', dark: '#0c1c21' },
        jungle: { light: '#dceee4', dark: '#0c1a14' },
    };
    var themeColour = (colours[theme] || colours.glass)[mode] || colours.glass.light;
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', themeColour);
    }
})();
</script>