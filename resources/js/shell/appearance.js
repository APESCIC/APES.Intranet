const STORAGE_KEY = 'apes.intranet.appearance';

const THEME_COLOURS = {
    glass: { light: '#c8eaf1', dark: '#0c1c21' },
    jungle: { light: '#dceee4', dark: '#0c1a14' },
};

const DEFAULTS = {
    theme: 'glass',
    mode: 'system',
    reducedTransparency: false,
};

function readStoredPreferences() {
  const html = document.documentElement;

  return {
    theme: html.getAttribute('data-theme') === 'jungle' ? 'jungle' : 'glass',
    mode: html.getAttribute('data-appearance-mode') || 'system',
    reducedTransparency: html.hasAttribute('data-reduced-transparency'),
  };
}

function resolveMode(mode) {
    if (mode === 'system') {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    return mode === 'dark' ? 'dark' : 'light';
}

function applyPreferences(prefs) {
    const html = document.documentElement;
    const theme = prefs.theme === 'jungle' ? 'jungle' : 'glass';
    const resolved = resolveMode(prefs.mode);

    html.setAttribute('data-theme', theme);
    html.setAttribute('data-mode', resolved);
    html.setAttribute('data-appearance-mode', prefs.mode);

    if (prefs.reducedTransparency) {
        html.setAttribute('data-reduced-transparency', '');
    } else {
        html.removeAttribute('data-reduced-transparency');
    }

    const palette = THEME_COLOURS[theme] || THEME_COLOURS.glass;
    const themeColour = palette[resolved] || palette.light;
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', themeColour);
    }
}

function writeLocalStorage(prefs) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch {
        // Ignore quota or privacy errors.
    }
}

async function persistPreferences(prefs) {
    writeLocalStorage(prefs);
    applyPreferences(prefs);

    const root = document.querySelector('[data-appearance-root]');
    const saveUrl = root?.getAttribute('data-appearance-save-url');
    if (!saveUrl) {
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        return;
    }

    try {
        await fetch(saveUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                theme: prefs.theme,
                mode: prefs.mode,
                reducedTransparency: prefs.reducedTransparency,
            }),
            credentials: 'same-origin',
        });
    } catch {
        // Keep local preference if the server is unreachable.
    }
}

function syncToggleLabel(button) {
    const resolved = document.documentElement.getAttribute('data-mode') === 'dark';
    button.setAttribute('aria-label', resolved ? 'Switch to light mode' : 'Switch to dark mode');
}

function wireModeToggle(button) {
    button.addEventListener('click', () => {
        const prefs = readStoredPreferences();
        const resolved = resolveMode(prefs.mode);
        const nextMode = resolved === 'dark' ? 'light' : 'dark';
        persistPreferences({ ...prefs, mode: nextMode });
        syncToggleLabel(button);
        document.dispatchEvent(new CustomEvent('appearance:change'));
    });
    syncToggleLabel(button);
}

function wireAppearancePanel(panel) {
    const themeInputs = panel.querySelectorAll('[data-appearance-theme]');
    const modeInputs = panel.querySelectorAll('[data-appearance-mode-option]');
    const reduced = panel.querySelector('[data-appearance-reduced-transparency]');

    const syncPanel = () => {
        const prefs = readStoredPreferences();
        themeInputs.forEach((input) => {
            input.checked = input.value === prefs.theme;
        });
        modeInputs.forEach((input) => {
            input.checked = input.value === prefs.mode;
        });
        if (reduced) {
            reduced.checked = prefs.reducedTransparency;
        }
    };

    const onChange = () => {
        const prefs = readStoredPreferences();
        const next = { ...prefs };

        const checkedTheme = panel.querySelector('[data-appearance-theme]:checked');
        if (checkedTheme) {
            next.theme = checkedTheme.value;
        }
        const checkedMode = panel.querySelector('[data-appearance-mode-option]:checked');
        if (checkedMode) {
            next.mode = checkedMode.value;
        }
        if (reduced) {
            next.reducedTransparency = reduced.checked;
        }

        persistPreferences(next);
        syncPanel();
        document.dispatchEvent(new CustomEvent('appearance:change'));
        const toggle = document.querySelector('[data-appearance-mode-toggle]');
        if (toggle) {
            syncToggleLabel(toggle);
        }
    };

    themeInputs.forEach((input) => input.addEventListener('change', onChange));
    modeInputs.forEach((input) => input.addEventListener('change', onChange));
    reduced?.addEventListener('change', onChange);
    document.addEventListener('appearance:change', syncPanel);
    syncPanel();
}

export function initAppearance() {
    const root = document.querySelector('[data-appearance-root]');
    if (!root) {
        return;
    }

    const prefs = readStoredPreferences();
    applyPreferences({ ...DEFAULTS, ...prefs });
    writeLocalStorage({ ...DEFAULTS, ...prefs });

    const toggle = root.querySelector('[data-appearance-mode-toggle]');
    if (toggle) {
        wireModeToggle(toggle);
    }

    const panel = root.querySelector('[data-appearance-panel]');
    if (panel) {
        wireAppearancePanel(panel);
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = readStoredPreferences();
        if (current.mode !== 'system') {
            return;
        }
        applyPreferences(current);
        if (toggle) {
            syncToggleLabel(toggle);
        }
    });
}