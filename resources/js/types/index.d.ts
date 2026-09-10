import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    submenu?: NavItem[]; 
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    emr_id: string; // Add this
    roles?: string[];
    access_type?: string | null;
    access_label?: string | null;
    navigation?: {
        dashboard: boolean;
        incoming: boolean;
        patients: boolean;
        appointments: boolean;
        beds: boolean;
        demographics: boolean;
        facilities: boolean;
        facilityHierarchy: boolean;
        religions: boolean;
        reports: boolean;
        providers: boolean;
        users: boolean;
        roles: boolean;
        permissions: boolean;
        dataEncryption: boolean;
        auditTrail: boolean;
    };
   
    [key: string]: unknown; // This allows for additional properties...
}


interface PermissionProps {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
}

type BreadcrumbItem = {
    title: string;
    href: string;
};
