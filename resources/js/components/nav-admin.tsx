import { Link, usePage } from "@inertiajs/react";
import { ChevronUp ,ChevronDown   } from 'lucide-react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { type NavItem } from '@/types';
import { useState } from "react";

export function NavAdministrator({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const [openMenu, setOpenMenu] = useState<string | null>(null);

    const toggleMenu = (menu: string) => {
        setOpenMenu(openMenu === menu ? null : menu);
    };

    return (
        <SidebarGroup className="group-data-[collapsible=icon]:hidden">
            <SidebarGroupLabel>Administrator</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.href}>
                        <SidebarMenuButton 
                            asChild 
                            isActive={route().current(item.href)}
                            onClick={() => item.submenu ? toggleMenu(item.title) : null}
                        >
                            {item.submenu ? (
                                // Button for expandable submenu
                                <button className="flex items-center w-full">
                                    <item.icon />
                                    <span className="ml-2">{item.title}</span>
                                    <span className="ml-auto">
                                        {openMenu === item.title ? <ChevronUp size={16}/> : <ChevronDown size={16}/>}
                                    </span>
                                </button>
                            ) : (
                                // Regular navigation link
                                <Link href={route(item.href)}>
                                    <item.icon />
                                    <span>{item.title}</span>
                                </Link>
                            )}
                        </SidebarMenuButton>

                        {/* Collapsible Submenu */}
                        {item.submenu && openMenu === item.title && (
                            <SidebarMenu className="ml-4 border-l border-gray-200">
                                {item.submenu.map((subitem) => (
                                    <SidebarMenuItem key={subitem.href}>
                                        <SidebarMenuButton asChild isActive={route().current(subitem.href)}>
                                            <Link href={route(subitem.href)}>
                                                <subitem.icon />
                                                <span>{subitem.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                            </SidebarMenu>
                        )}
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
