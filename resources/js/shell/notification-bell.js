export function initNotificationBell() {
    const root = document.querySelector('[data-notification-bell]');
    if (!root) {
        return;
    }

    const toggle = root.querySelector('[data-notification-bell-toggle]');
    const panel = root.querySelector('[data-notification-bell-panel]');
    if (!toggle || !panel) {
        return;
    }

    const setOpen = (open) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.hidden = !open;
    };

    toggle.addEventListener('click', () => {
        setOpen(panel.hidden);
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setOpen(false);
            toggle.focus();
        }
    });
}
