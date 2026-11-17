export const initTopNav = () => {
    const root = document.querySelector('[data-workspace-top-nav]');
    if (!root) {
        return;
    }

    const disclosures = [...root.querySelectorAll('[data-workspace-disclosure]')];

    const closeAll = ({ except = null } = {}) => {
        for (const disclosure of disclosures) {
            if (disclosure === except) {
                continue;
            }
            const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
            const panel = disclosure.querySelector('[data-workspace-disclosure-panel]');
            if (!trigger || !panel) {
                continue;
            }
            trigger.setAttribute('aria-expanded', 'false');
            panel.hidden = true;
        }
    };

    const open = (disclosure) => {
        const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
        const panel = disclosure.querySelector('[data-workspace-disclosure-panel]');
        if (!trigger || !panel) {
            return;
        }
        closeAll({ except: disclosure });
        trigger.setAttribute('aria-expanded', 'true');
        panel.hidden = false;
    };

    const toggle = (disclosure) => {
        const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
        if (!trigger) {
            return;
        }
        if (trigger.getAttribute('aria-expanded') === 'true') {
            closeAll();
        } else {
            open(disclosure);
        }
    };

    for (const disclosure of disclosures) {
        const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
        if (!trigger) {
            continue;
        }
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            toggle(disclosure);
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }
        const openTrigger = root.querySelector('[data-workspace-disclosure-trigger][aria-expanded="true"]');
        if (!openTrigger) {
            return;
        }
        closeAll();
        openTrigger.focus();
    });

    document.addEventListener('pointerdown', (event) => {
        if (root.contains(event.target)) {
            return;
        }
        closeAll();
    });
};
