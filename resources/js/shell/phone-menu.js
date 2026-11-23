import { trapDialog } from './dialog-focus.js';

const PHONE_MAX = '(max-width: 63.9375rem)';

const closePhoneDisclosure = (disclosure) => {
    const trigger = disclosure.querySelector('[data-workspace-phone-disclosure-trigger]');
    const panel = disclosure.querySelector('[data-workspace-phone-disclosure-panel]');
    if (!trigger || !panel) {
        return;
    }
    trigger.setAttribute('aria-expanded', 'false');
    panel.hidden = true;
};

const togglePhoneDisclosure = (disclosure) => {
    const trigger = disclosure.querySelector('[data-workspace-phone-disclosure-trigger]');
    if (!trigger) {
        return;
    }
    if (trigger.getAttribute('aria-expanded') === 'true') {
        closePhoneDisclosure(disclosure);
    } else {
        const nav = disclosure.closest('[data-workspace-phone-menu-nav]');
        nav?.querySelectorAll('[data-workspace-phone-disclosure]').forEach((other) => {
            if (other !== disclosure) {
                closePhoneDisclosure(other);
            }
        });
        const panel = disclosure.querySelector('[data-workspace-phone-disclosure-panel]');
        trigger.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
    }
};

export const initPhoneMenu = () => {
    const dock = document.querySelector('[data-workspace-phone-menu]');
    const openTrigger = document.querySelector('[data-workspace-phone-menu-open]');
    const overlay = dock?.querySelector('[data-workspace-phone-menu-overlay]');
    const panel = dock?.querySelector('[data-workspace-phone-menu-panel]');
    const closeTrigger = dock?.querySelector('[data-workspace-phone-menu-close]');
    const page = document.querySelector('[data-workspace-page]');

    if (!dock || !openTrigger || !overlay || !panel || !closeTrigger) {
        return;
    }

    let releaseTrap = null;

    const isPhone = () => window.matchMedia(PHONE_MAX).matches;

    const setOpenState = (open) => {
        openTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        dock.hidden = !open;
        overlay.hidden = !open;
        overlay.dataset.state = open ? 'open' : 'closed';
    };

    const closeMenu = ({ restoreFocus = true } = {}) => {
        releaseTrap?.({ restoreFocus });
        releaseTrap = null;
        setOpenState(false);
        panel.querySelectorAll('[data-workspace-phone-disclosure]').forEach(closePhoneDisclosure);
    };

    const openMenu = () => {
        if (!isPhone()) {
            return;
        }
        setOpenState(true);
        releaseTrap = trapDialog(panel, {
            onClose: () => closeMenu({ restoreFocus: true }),
            initialFocus: '[data-workspace-phone-menu-close]',
            fallbackFocus: openTrigger,
            inert: [page],
        });
    };

    openTrigger.addEventListener('click', (event) => {
        event.preventDefault();
        if (openTrigger.getAttribute('aria-expanded') === 'true') {
            closeMenu({ restoreFocus: true });
        } else {
            openMenu();
        }
    });

    closeTrigger.addEventListener('click', (event) => {
        event.preventDefault();
        closeMenu({ restoreFocus: true });
    });

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeMenu({ restoreFocus: true });
        }
    });

    panel.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-workspace-phone-disclosure-trigger]');
        if (!trigger || !panel.contains(trigger)) {
            return;
        }
        const disclosure = trigger.closest('[data-workspace-phone-disclosure]');
        if (!disclosure) {
            return;
        }
        event.preventDefault();
        togglePhoneDisclosure(disclosure);
    });

    window.matchMedia(PHONE_MAX).addEventListener('change', (event) => {
        if (!event.matches && openTrigger.getAttribute('aria-expanded') === 'true') {
            closeMenu({ restoreFocus: false });
        }
    });
};