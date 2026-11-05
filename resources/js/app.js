import {
    BookOpen,
    Briefcase,
    CalendarDays,
    ClipboardList,
    Clock,
    createIcons,
    ExternalLink,
    FilePenLine,
    FlaskConical,
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    LogIn,
    LogOut,
    Menu,
    MessagesSquare,
    Network,
    Newspaper,
    ScrollText,
    ShieldCheck,
    Users,
    X,
} from 'lucide';
import { initDrawer } from './shell/drawer.js';

const icons = {
    BookOpen,
    Briefcase,
    CalendarDays,
    ClipboardList,
    Clock,
    ExternalLink,
    FilePenLine,
    FlaskConical,
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    LogIn,
    LogOut,
    Menu,
    MessagesSquare,
    Network,
    Newspaper,
    ScrollText,
    ShieldCheck,
    Users,
    X,
};

function init() {
    createIcons({
        icons,
        attrs: {
            'aria-hidden': 'true',
            focusable: 'false',
        },
    });
    initDrawer();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
