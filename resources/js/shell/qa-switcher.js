const DESKTOP = '(min-width: 64rem)';

const pillFor = (flyout) => flyout.closest('[data-qa-dock]')?.querySelector('[data-qa-pill]') ?? null;

/**
 * The local QA role switcher: an amber pill in the rail (and its copy in the
 * drawer) that opens a popover listing the QA profiles.
 */
export const initQaSwitcher = () => {
    // Capture, because toggle does not bubble and the drawer copy is added later.
    document.addEventListener('toggle', (event) => {
        const flyout = event.target;
        if (!(flyout instanceof HTMLElement) || !flyout.matches('[data-qa-flyout]')) {
            return;
        }

        const open = event.newState === 'open';
        pillFor(flyout)?.setAttribute('aria-expanded', String(open));
        if (open) {
            flyout.querySelector('[aria-current="true"]')?.focus();
        }
    }, true);

    // Capture, so Escape closes only the switcher and not the drawer around it.
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const flyout = document.querySelector('[data-qa-flyout]:popover-open');
        if (!flyout) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        flyout.hidePopover();
        pillFor(flyout)?.focus();
    }, true);

    const pending = document.querySelector('[data-workspace-user-menu] [data-qa-flyout][data-qa-autoopen]')
        ?? document.querySelector('[data-workspace-rail-dock] [data-qa-flyout][data-qa-autoopen]');
    if (pending && window.matchMedia(DESKTOP).matches) {
        pending.showPopover();
    }
};
