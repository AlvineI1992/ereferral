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

import { useMemo } from 'react';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid }, // Changed to route
    { title: 'Incoming', href: '/incoming', icon: Inbox }, // Changed to route
    { title: 'Outgoing', href: '/outgoing', icon: ExternalLink }, // Changed to route
    { title: 'Patient', href: '/patient_registry', icon: BriefcaseMedical }, // Changed to route
    { title: 'Records', href: '/records', icon: FileBadge }, // Changed to route
    { title: 'Appointments', href: '/appointments', icon: Calendar1 }, // Changed to route
    { title: 'Bed Tracker', href: '/bed_tracker', icon: BedDouble }, // Changed to route
];

const navReferences: NavItem[] = [
    { title: 'Demographics', href: '/demographics', icon: MapPinned }, // Changed to route
    { title: 'Facilities', href: '/facilities', icon: Hospital }, // Changed to route
];

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage();
    const user = props.auth?.user;
    const userRoles = user?.roles || [];

    const getRouteOrFallback = (routeName: string, fallback: string) => {
        try {
            return route(routeName);
        } catch (error) {
            console.warn(`Error resolving route: ${routeName}, using fallback.`);
            return fallback;
        }
    };

    // Check roles (case-insensitive)
    const hasAdminRole = useMemo(() => {
        const normalized = userRoles.map((r: string) => r.toLowerCase());
        return normalized.includes('admin') || normalized.includes('super-admin');
    }, [userRoles]);

    const adminNavItems: NavItem[] = [
        {
            title: 'Admin',
            href: '#',
            icon: User,
            submenu: [
                { title: 'Provider', href: 'emr.index', icon: CircleChevronRight },
                { title: 'Users', href: 'user.index', icon: CircleChevronRight },
                { title: 'Roles', href: 'roles.index', icon: CircleChevronRight },
                { title: 'Permissions', href: 'permission.index', icon: CircleChevronRight },
            ],
        },
    ];

    console.log(hasAdminRole); // Can be used for debugging or conditional rendering

    return (
        <Sidebar collapsible="offcanvas" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={getRouteOrFallback('dashboard.index', '/dashboard')} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavReference items={navReferences} />
                
                {hasAdminRole && <NavAdministrator items={adminNavItems} />} {/* Conditionally render the admin menu */}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
