export const initHelpdeskTopicsEditor = () => {
    const root = document.querySelector('[data-helpdesk-topics]');
    const form = root?.querySelector('[data-topic-form]');
    const dirtyBanner = root?.querySelector('[data-topic-dirty]');
    const discardButton = root?.querySelector('[data-topic-discard]');

    if (!root || !form || !dirtyBanner || !discardButton) {
        return;
    }

    const snapshot = () => new FormData(form);
    let baseline = snapshot();

    const serialize = (data) => {
        const entries = [...data.entries()].map(([key, value]) => `${key}=${String(value)}`);
        entries.sort();

        return entries.join('&');
    };

    const isDirty = () => serialize(snapshot()) !== serialize(baseline);

    const refreshDirtyState = () => {
        const dirty = isDirty();
        dirtyBanner.hidden = !dirty;
        dirtyBanner.setAttribute('aria-hidden', dirty ? 'false' : 'true');
    };

    const applyValues = (data) => {
        for (const [key, value] of data.entries()) {
            const field = form.elements.namedItem(key);
            if (!field || typeof field === 'RadioNodeList') {
                continue;
            }

            if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                field.checked = value === '1' || value === 'on' || value === 'true';
            } else if ('value' in field) {
                field.value = String(value);
            }
        }

        for (const field of form.querySelectorAll('input[type="checkbox"]')) {
            if (!(field instanceof HTMLInputElement) || !field.name) {
                continue;
            }
            if (!data.has(field.name)) {
                field.checked = false;
            }
        }
    };

    form.addEventListener('input', refreshDirtyState);
    form.addEventListener('change', refreshDirtyState);

    discardButton.addEventListener('click', () => {
        applyValues(baseline);
        refreshDirtyState();
        discardButton.focus();
    });

    form.addEventListener('submit', () => {
        baseline = snapshot();
        refreshDirtyState();
    });

    refreshDirtyState();
};
