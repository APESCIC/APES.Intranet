import {
    Activity,
    AppWindow,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
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
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    Megaphone,
    Menu,
    MessagesSquare,
    Network,
    Newspaper,
    ScrollText,
    Shield,
    ShieldCheck,
    ShieldHalf,
    Users,
    X,
} from 'lucide';
import { initDrawer } from './shell/drawer.js';

const icons = {
    Activity,
    AppWindow,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
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
    KeyRound,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    Megaphone,
    Menu,
    MessagesSquare,
    Network,
    Newspaper,
    ScrollText,
    Shield,
    ShieldCheck,
    ShieldHalf,
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
