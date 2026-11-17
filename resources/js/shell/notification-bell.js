const wireBell = (root) => {
    const toggle = root.querySelector('[data-notification-bell-toggle]');
    const panel = root.querySelector('[data-notification-bell-panel]');
    if (!toggle || !panel || typeof panel.showPopover !== 'function') {
        return;
    }

    const syncExpanded = () => {
        toggle.setAttribute('aria-expanded', panel.matches(':popover-open') ? 'true' : 'false');
    };

    panel.addEventListener('toggle', syncExpanded);

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        if (panel.matches(':popover-open')) {
            panel.hidePopover();
            toggle.focus();
        } else {
            panel.showPopover();
        }
        syncExpanded();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !panel.matches(':popover-open')) {
            return;
        }
        panel.hidePopover();
        toggle.focus();
    });
};

export function initNotificationBell() {
    document.querySelectorAll('[data-notification-bell]').forEach((root) => wireBell(root));
}
