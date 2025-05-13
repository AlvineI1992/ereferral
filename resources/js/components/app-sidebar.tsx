import { NavAdministrator } from '@/components/nav-admin';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavReference } from '@/components/nav-references';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BedDouble,
    BriefcaseMedical,
    Calendar1,
    CircleChevronRight,
    ExternalLink,
    FileBadge,
    Hospital,
    Inbox,
    LayoutGrid,
    MapPinned,
    User,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Incoming', href: '/incoming', icon: Inbox },
    { title: 'Outgoing', href: '/dashboard', icon: ExternalLink },
    { title: 'Patient', href: '/dashboard', icon: BriefcaseMedical },
    { title: 'Records', href: '/dashboard', icon: FileBadge },
    { title: 'Appointments', href: '/dashboard', icon: Calendar1 },
    { title: 'Bed Tracker', href: '/dashboard', icon: BedDouble },
];

const navReferences: NavItem[] = [
    { title: 'Demographics', href: '/dashboard', icon: MapPinned },
    { title: 'Facilities', href: '/facilities', icon: Hospital },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Administrator',
        href: '#',
        icon: User,
        submenu: [
            { title: 'Manage Provider', href: 'emr', icon: CircleChevronRight },
            { title: 'Manage Users', href: 'users', icon: CircleChevronRight },
            { title: 'Roles', href: 'roles', icon: CircleChevronRight },
            { title: 'Permissions', href: 'permission', icon: CircleChevronRight },
        ],
    },
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage();
    const user = props.auth?.user;
    const userRoles = user?.roles || [];

    return (
        <Sidebar collapsible="offcanvas" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                 {/* Only show admin section for users with admin role */}
                    {userRoles.some(role => role.name === 'Admin') && (
                        <>
                            <NavReference items={navReferences} />
                            <NavAdministrator items={adminNavItems} />
                        </>
                    )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
