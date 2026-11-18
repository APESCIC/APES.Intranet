const closeDisclosure = (disclosure) => {
    const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
    const panel = disclosure.querySelector('[data-workspace-disclosure-panel]');
    if (!trigger || !panel) {
        return;
    }
    trigger.setAttribute('aria-expanded', 'false');
    panel.hidden = true;
};

const openDisclosure = (disclosure) => {
    const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
    const panel = disclosure.querySelector('[data-workspace-disclosure-panel]');
    if (!trigger || !panel) {
        return;
    }
    trigger.setAttribute('aria-expanded', 'true');
    panel.hidden = false;
};

const toggleDisclosure = (disclosure) => {
    const trigger = disclosure.querySelector('[data-workspace-disclosure-trigger]');
    if (!trigger) {
        return;
    }
    if (trigger.getAttribute('aria-expanded') === 'true') {
        closeDisclosure(disclosure);
    } else {
        openDisclosure(disclosure);
    }
};

const layoutOverflow = (root) => {
    const more = root.querySelector('[data-workspace-top-nav-more]');
    const morePanel = root.querySelector('[data-workspace-top-nav-more-panel]');
    const items = [...root.querySelectorAll('[data-workspace-top-nav-item]')];
    if (!more || !morePanel || items.length === 0) {
        return;
    }

    // Reset: show every section in the bar and clear More.
    more.hidden = true;
    morePanel.replaceChildren();
    for (const item of items) {
        item.hidden = false;
        closeDisclosure(item);
    }
    closeDisclosure(more);

    const available = root.clientWidth;
    if (available <= 0) {
        return;
    }

    more.hidden = false;
    const moreWidth = more.getBoundingClientRect().width;
    more.hidden = true;

    let used = 0;
    const home = root.querySelector('[data-workspace-top-nav-home]');
    if (home) {
        used += home.getBoundingClientRect().width;
    }

    const styles = getComputedStyle(root);
    const gap = Number.parseFloat(styles.columnGap || styles.gap || '0') || 0;
    const overflow = [];

    for (const item of items) {
        const width = item.getBoundingClientRect().width;
        const next = used + (used > 0 ? gap : 0) + width;
        const reserveMore = moreWidth + gap;
        if (next + reserveMore <= available) {
            used = next;
            continue;
        }
        overflow.push(item);
    }

    if (overflow.length === 0) {
        return;
    }

    for (const item of overflow) {
        item.hidden = true;
        closeDisclosure(item);

        const heading = item.dataset.topNavHeading
            ?? item.querySelector('[data-workspace-disclosure-trigger] span')?.textContent?.trim()
            ?? 'Section';
        const sourcePanel = item.querySelector('[data-workspace-disclosure-panel]');
        const section = document.createElement('section');
        section.className = 'sg-top-nav__more-section';
        const title = document.createElement('h3');
        title.className = 'sg-top-nav__more-heading';
        title.textContent = heading;
        section.append(title);
        if (sourcePanel) {
            for (const child of sourcePanel.children) {
                section.append(child.cloneNode(true));
            }
        }
        morePanel.append(section);
    }

    more.hidden = false;
};

export const initTopNav = () => {
    const root = document.querySelector('[data-workspace-top-nav]');
    if (!root) {
        return;
    }

    const closeAll = ({ except = null } = {}) => {
        for (const disclosure of root.querySelectorAll('[data-workspace-disclosure]')) {
            if (disclosure === except) {
                continue;
            }
            closeDisclosure(disclosure);
        }
    };

    root.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-workspace-disclosure-trigger]');
        if (!trigger || !root.contains(trigger)) {
            return;
        }
        const disclosure = trigger.closest('[data-workspace-disclosure]');
        if (!disclosure) {
            return;
        }
        event.preventDefault();
        if (trigger.getAttribute('aria-expanded') === 'true') {
            closeDisclosure(disclosure);
        } else {
            closeAll({ except: disclosure });
            openDisclosure(disclosure);
        }
    });

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

    let frame = 0;
    const scheduleLayout = () => {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => layoutOverflow(root));
    };

    scheduleLayout();
    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(scheduleLayout);
        observer.observe(root);
        const topbar = root.closest('.sg-topbar');
        if (topbar) {
            observer.observe(topbar);
        }
    } else {
        window.addEventListener('resize', scheduleLayout);
    }
    window.addEventListener('load', scheduleLayout);
};
