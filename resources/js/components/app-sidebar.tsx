import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { NavReference } from '@/components/nav-references';
import { NavAdministrator } from '@/components/nav-admin';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

import { Link } from '@inertiajs/react';
import { LayoutGrid,User,CircleChevronRight,Inbox,ExternalLink,BookUser,Hospital,MonitorCog ,MapPinned} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Incoming',
        href: '/incoming',
        icon: Inbox,
    },
    {
        title: 'Outgoing',
        href: '/dashboard',
        icon: ExternalLink ,
    },
    {
        title: 'Patients',
        href: '/dashboard',
        icon: BookUser,
    },
    {
        title: 'Records',
        href: '/dashboard',
        icon: BookUser,
    },
];

const navReferences: NavItem[] = [
    {
        title: 'Demographics',
        href: '/dashboard',
        icon: MapPinned,
    },
   
    {
        title: 'Facilities',
        href: '/facilities',
        icon: Hospital ,
    }
];
const adminNavItems: NavItem[] = [
    {
        title: 'Administrator', // Main menu item "Administrator"
        href: '#', // No direct link, dropdown only
        icon: User,
        submenu: [
            {
                title: 'Manage Provider',
                href: 'emr',
                icon: CircleChevronRight,
            },
            {
                title: 'Manage Users',
                href: 'users',
                icon: CircleChevronRight,
            },
            {
                title: 'Roles',
                href: 'roles',
                icon: CircleChevronRight,
            },
            {
                title: 'Permissions',
                href: 'permission',
                icon: CircleChevronRight,
            },
        ],
    }
  
    
];



const footerNavItems: NavItem[] = [
   
];

export function AppSidebar() {
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
                <NavReference items={navReferences} />
                <NavAdministrator items={adminNavItems} /> 
            </SidebarContent>

            <SidebarFooter>
            {    <NavFooter items={footerNavItems}  />}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
