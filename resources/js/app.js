import {
    Activity,
    AppWindow,
    Bell,
    BellRing,
    Blocks,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
    ChartBar,
    ChartColumn,
    ChartPie,
    ChevronDown,
    CircleUser,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Cloud,
    createIcons,
    ExternalLink,
    FilePen,
    FilePenLine,
    FileText,
    FlaskConical,
    Folder,
    GraduationCap,
    Heart,
    History,
    IdCard,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    Library,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    Megaphone,
    Menu,
    Moon,
    MessagesSquare,
    MonitorCog,
    Network,
    Newspaper,
    Plus,
    ScrollText,
    Sun,
    Shield,
    ShieldCheck,
    ShieldHalf,
    Tags,
    Timer,
    Users,
    X,
} from 'lucide';
import { initAppearance } from './shell/appearance.js';
import { initClockForm } from './operations/clock.js';
import { initSignaturePads } from './operations/signature.js';
import { initPushPreferences, registerPushServiceWorker } from './push.js';
import { initNotificationBell } from './shell/notification-bell.js';
import { initQaSwitcher } from './shell/qa-switcher.js';
import { initPhoneMenu } from './shell/phone-menu.js';
import { initTopNav } from './shell/top-nav.js';
import { initUserMenu } from './shell/user-menu.js';

const icons = {
    Activity,
    AppWindow,
    Bell,
    BellRing,
    Blocks,
    BookOpen,
    Boxes,
    Briefcase,
    CalendarCheck,
    CalendarDays,
    ChartBar,
    ChartColumn,
    ChartPie,
    ChevronDown,
    CircleUser,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Cloud,
    ExternalLink,
    FilePen,
    FilePenLine,
    FileText,
    FlaskConical,
    Folder,
    GraduationCap,
    Heart,
    History,
    IdCard,
    KeyRound,
    LayoutDashboard,
    LayoutGrid,
    Library,
    LifeBuoy,
    ListChecks,
    ListTodo,
    LogIn,
    LogOut,
    Mail,
    MapPin,
    Megaphone,
    Menu,
    Moon,
    MessagesSquare,
    MonitorCog,
    Network,
    Newspaper,
    Plus,
    ScrollText,
    Sun,
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
    initAppearance();
    initPhoneMenu();
    initTopNav();
    initUserMenu();
    initQaSwitcher();
    initNotificationBell();
    initClockForm();
    initSignaturePads();
    initPushPreferences();
    // Register only — never ask for notification permission on page load.
    registerPushServiceWorker().catch(() => {});
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
