import { NavAdministrator } from '@/components/nav-admin';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavReference } from '@/components/nav-references';
import { NavReports } from '@/components/nav-reports';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    BedDouble,
    BriefcaseMedical,
    Calendar1,
    ChartNoAxesColumnIncreasing,
    CircleChevronRight,
    FileBadge,
    Hospital,
    Inbox,
    LayoutGrid,
    MapPinned,
    ShieldCheck,
    ScrollText,
    User,
} from 'lucide-react';
import AppLogo from './app-logo';

import { useMemo } from 'react';

const footerNavItems: NavItem[] = [];

export function AppSidebar() {
    const { props } = usePage();
    const user = props.auth?.user;
    const access = user?.navigation;
    const mainNavItems: NavItem[] = [
        ...(access?.dashboard ? [{ title: 'Dashboard', href: '/dashboard', icon: LayoutGrid }] : []),
        ...(access?.incoming ? [{ title: 'Referral/s', href: '/incoming', icon: Inbox }] : []),
        ...(access?.patients ? [{ title: 'Patient registry', href: '/patient_registry', icon: BriefcaseMedical }] : []),
        ...(access?.appointments ? [{ title: 'Appointments', href: '/appointments', icon: Calendar1 }] : []),
        ...(access?.beds ? [{ title: 'Bed Tracker', href: '/bed_tracker', icon: BedDouble }] : []),
    ];
    const navReferences: NavItem[] = [
        ...(access?.demographics ? [{ title: 'Demographics', href: '/demographics', icon: MapPinned }] : []),
        ...(access?.facilities ? [{ title: 'Facilities', href: '/facilities', icon: Hospital }] : []),
        ...(access?.facilityHierarchy
            ? [{ title: 'Facility Hierarchy', href: '/facility-hierarchy', icon: CircleChevronRight }]
            : []),
        ...(access?.religions ? [{ title: 'Religions', href: '/religions', icon: FileBadge }] : []),
    ];
    const navReports: NavItem[] = access?.reports
        ? [{ title: 'Referral Report', href: '/reports/referrals-by-facility', icon: ChartNoAxesColumnIncreasing }]
        : [];
    const sidebarHome = mainNavItems[0]?.href ?? navReferences[0]?.href ?? navReports[0]?.href ?? (access?.auditTrail ? '/admin/audit-trail' : '#');

    const getRouteOrFallback = (routeName: string, fallback: string) => {
        try {
            return route(routeName);
        } catch {
            console.warn(`Error resolving route: ${routeName}, using fallback.`);
            return fallback;
        }
    };

    // Check roles (case-insensitive)
    const hasAdminRole = useMemo(() => {
        const normalized = (Array.isArray(user?.roles) ? user.roles : []).map((role: string) => role.toLowerCase());
        return normalized.includes('admin') || normalized.includes('super-admin');
    }, [user?.roles]);

    const adminNavItems: NavItem[] = [
        {
            title: 'Admin',
            href: '#',
            icon: User,
            submenu: [
                ...(access?.providers ? [{ title: 'Provider', href: 'emr.index', icon: CircleChevronRight }] : []),
                ...(access?.users ? [{ title: 'Users', href: 'user.index', icon: CircleChevronRight }] : []),
                ...(access?.roles ? [{ title: 'Roles', href: 'roles.index', icon: CircleChevronRight }] : []),
                ...(access?.permissions ? [{ title: 'Permissions', href: 'permission.index', icon: CircleChevronRight }] : []),
                ...(access?.dataEncryption ? [{ title: 'Data Encryption', href: 'admin.data-encryption.index', icon: ShieldCheck }] : []),
                ...(access?.auditTrail ? [{ title: 'Audit Trail', href: 'admin.audit-trail.index', icon: ScrollText }] : []),
            ],
        },
    ];

    return (
        <Sidebar collapsible="offcanvas" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={access?.dashboard ? getRouteOrFallback('dashboard', '/dashboard') : sidebarHome} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {mainNavItems.length > 0 && <NavMain items={mainNavItems} />}
                {navReferences.length > 0 && <NavReference items={navReferences} />}
                {navReports.length > 0 && <NavReports items={navReports} />}
                {hasAdminRole && adminNavItems[0].submenu!.length > 0 && <NavAdministrator items={adminNavItems} />}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
