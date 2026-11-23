import { trapDialog } from '../shell/dialog-focus.js';

const stateUrlFor = (slug) => `/admin/modules/${encodeURIComponent(slug)}/state`;

export const initModulesStateDialog = () => {
    const root = document.querySelector('[data-workspace-modules-index]');
    const dialog = document.querySelector('[data-module-state-dialog]');
    const form = dialog?.querySelector('[data-module-state-form]');
    if (!root || !dialog || !form) {
        return;
    }

    const title = dialog.querySelector('[data-module-state-title]');
    const desc = dialog.querySelector('[data-module-state-desc]');
    const enabledInput = dialog.querySelector('[data-module-state-enabled]');
    const reasonInput = dialog.querySelector('[data-module-state-reason]');
    const cancelButton = dialog.querySelector('[data-module-state-cancel]');
    const page = document.querySelector('[data-workspace-page]');

    let releaseTrap = null;
    let trigger = null;

    const closeDialog = ({ restoreFocus = true } = {}) => {
        if (dialog.open) {
            dialog.close();
        }
        releaseTrap?.({ restoreFocus });
        releaseTrap = null;
        form.reset();
        trigger = null;
    };

    const openDialog = (button) => {
        const slug = button.dataset.moduleSlug;
        const name = button.dataset.moduleName ?? slug;
        const currentlyEnabled = button.dataset.moduleEnabled === 'true';
        const nextEnabled = button.dataset.moduleNextEnabled ?? (currentlyEnabled ? '0' : '1');

        trigger = button;
        form.action = stateUrlFor(slug);
        enabledInput.value = nextEnabled;

        if (currentlyEnabled) {
            title.textContent = `Disable ${name}`;
            desc.textContent = 'Routes, navigation and background work for this module stop. Data is kept. A reason is recorded in the audit log.';
            form.querySelector('[data-module-state-submit]').textContent = 'Disable module';
        } else {
            title.textContent = `Enable ${name}`;
            desc.textContent = 'The module becomes accessible again. A reason is recorded in the audit log.';
            form.querySelector('[data-module-state-submit]').textContent = 'Enable module';
        }

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }

        releaseTrap = trapDialog(dialog, {
            onClose: () => closeDialog({ restoreFocus: true }),
            initialFocus: '[data-module-state-reason]',
            fallbackFocus: trigger,
            inert: [page],
        });

        reasonInput?.focus();
    };

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-module-state-open]');
        if (!button || !root.contains(button)) {
            return;
        }
        event.preventDefault();
        openDialog(button);
    });

    cancelButton?.addEventListener('click', (event) => {
        event.preventDefault();
        closeDialog({ restoreFocus: true });
    });

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog({ restoreFocus: true });
    });

    dialog.addEventListener('close', () => {
        releaseTrap?.({ restoreFocus: true });
        releaseTrap = null;
    });
};