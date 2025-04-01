import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { NavAdministrator } from '@/components/nav-admin';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid,User,CircleChevronRight } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
  
    
];

const adminNavItems: NavItem[] = [
    {
        title: 'Administrator', // Main menu item "Administrator"
        href: '#', // No direct link, dropdown only
        icon: User,
        submenu: [
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
        ],
    }
  
    
];



const footerNavItems: NavItem[] = [
   
];

export function AppSidebar() {
    return (
        <Sidebar  collapsible="icon" variant="inset">
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
                <NavAdministrator items={adminNavItems} /> 
            </SidebarContent>

            <SidebarFooter>
            {    <NavFooter items={footerNavItems}  />}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
