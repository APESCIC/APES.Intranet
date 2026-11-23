<?php

it('shell and theme css disable backdrop blur for reduced transparency', function () {
    $themes = file_get_contents(base_path('resources/css/themes.css'));
    $shell = file_get_contents(base_path('resources/css/shell.css'));

    expect($themes)
        ->toContain('html[data-reduced-transparency] *')
        ->toContain('backdrop-filter: none !important')
        ->toContain('-webkit-backdrop-filter: none !important')
        ->toContain('@media (prefers-reduced-transparency: reduce)')
        ->toContain('html[data-reduced-transparency][data-theme=\'glass\'][data-mode=\'light\']');

    expect($shell)
        ->toContain('--sg-blur-card: 8px')
        ->toContain('--sg-blur-rail: 10px')
        ->toContain('@media (max-width: 48rem)')
        ->toContain('@media (prefers-reduced-motion: reduce)')
        ->toContain('.sg-disclosure__chevron')
        ->toContain('.sg-overlay[data-motion]');
});
