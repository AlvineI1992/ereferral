import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import RolesManagement from './RolesManagement';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
];
export default function Roles({
    canCreateRole,
    canEditRole,
    canDeleteRole,
    canViewRole,
    canAssignRole,
}: {
    canCreateRole: boolean;
    canEditRole:boolean,
    canDeleteRole:boolean,
    canViewRole:boolean,
    canAssignRole:boolean,

}) {
    const [refreshKey, setRefreshKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const handleRoleCreated = () => {
        setRefreshKey((prev) => prev + 1); // triggers reload of RolesList
    };
    useEffect(() => {
        // Simulate a network request or data fetching
        setLoading(true);
        setTimeout(() => {
            setLoading(false);
        }, 2000); // Simulate loading for 2 seconds
    }, [refreshKey]);
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            <div className="lg:col-span-1">
                <RolesManagement onRoleCreated={() => {}}
                 canCreate={canCreateRole} 
                 canEdit={canEditRole} 
                 canDelete={canDeleteRole} 
                 canView={canViewRole} 
                 canAssign={canAssignRole} 
                />
            </div>
        </AppLayout>
    );
}
