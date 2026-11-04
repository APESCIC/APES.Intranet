import { createIcons, House, LifeBuoy, ShieldCheck, Users } from 'lucide';

const icons = {
    House,
    LifeBuoy,
    ShieldCheck,
    Users,
};

function renderIcons() {
    createIcons({
        icons,
        attrs: {
            'aria-hidden': 'true',
            focusable: 'false',
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderIcons);
} else {
    renderIcons();
}
