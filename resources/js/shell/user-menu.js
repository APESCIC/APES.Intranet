export const initUserMenu = () => {
    const root = document.querySelector('[data-workspace-user-menu]');
    if (!root) {
        return;
    }

    const trigger = root.querySelector('[data-workspace-user-menu-trigger]');
    const panel = root.querySelector('[data-workspace-user-menu-panel]');
    if (!trigger || !panel) {
        return;
    }

    const close = ({ restoreFocus = false } = {}) => {
        trigger.setAttribute('aria-expanded', 'false');
        panel.hidden = true;
        if (restoreFocus) {
            trigger.focus();
        }
    };

    const open = () => {
        trigger.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        if (trigger.getAttribute('aria-expanded') === 'true') {
            close();
        } else {
            open();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        if (trigger.getAttribute('aria-expanded') !== 'true') {
            return;
        }
        // Let an open QA popover handle Escape first.
        if (document.querySelector('[data-qa-flyout]:popover-open')) {
            return;
        }
        event.preventDefault();
        close({ restoreFocus: true });
    });

    document.addEventListener('pointerdown', (event) => {
        if (root.contains(event.target)) {
            return;
        }
        if (event.target instanceof Element && event.target.closest('[data-qa-flyout]')) {
            return;
        }
        close();
    });
};
