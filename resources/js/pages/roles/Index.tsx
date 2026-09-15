import AccessDirectory from '@/components/access-directory';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Index({
    canCreateRole,
    canEditRole,
    canDeleteRole,
    canViewRole,
    canAssignRole,
}: {
    canCreateRole: boolean;
    canEditRole: boolean;
    canDeleteRole: boolean;
    canViewRole: boolean;
    canAssignRole: boolean;
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Roles', href: '/roles' }]}>
            <Head title="Roles" />
            <AccessDirectory
                kind="roles"
                canCreate={canCreateRole}
                canEdit={canEditRole}
                canDelete={canDeleteRole}
                canView={canViewRole}
                canAssign={canAssignRole}
            />
        </AppLayout>
    );
}
