import {
    Activity,
    AppWindow,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
    ChartColumn,
    CircleUser,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Cloud,
    createIcons,
    ExternalLink,
    FilePenLine,
    FileText,
    FlaskConical,
    Folder,
    GraduationCap,
    History,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    Megaphone,
    Menu,
    MessagesSquare,
    MonitorCog,
    Network,
    Newspaper,
    Plus,
    ScrollText,
    Shield,
    ShieldCheck,
    ShieldHalf,
    Tags,
    Timer,
    Users,
    X,
} from 'lucide';
import { initClockForm } from './operations/clock.js';
import { initSignaturePads } from './operations/signature.js';
import { registerPushServiceWorker } from './push.js';
import { initDrawer } from './shell/drawer.js';
import { initQaSwitcher } from './shell/qa-switcher.js';

const icons = {
    Activity,
    AppWindow,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
    ChartColumn,
    CircleUser,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Cloud,
    ExternalLink,
    FilePenLine,
    FileText,
    FlaskConical,
    Folder,
    GraduationCap,
    History,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    Megaphone,
    Menu,
    MessagesSquare,
    MonitorCog,
    Network,
    Newspaper,
    Plus,
    ScrollText,
    Shield,
    ShieldCheck,
    ShieldHalf,
    Tags,
    Timer,
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
    initQaSwitcher();
    initClockForm();
    initSignaturePads();
    // Register only — never ask for notification permission on page load.
    registerPushServiceWorker().catch(() => {});
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
