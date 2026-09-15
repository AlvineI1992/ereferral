import AccessDirectory from '@/components/access-directory';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Index({
    canCreatePermission,
    canEditPermission,
    canDeletePermission,
    canViewPermission,
}: {
    canCreatePermission: boolean;
    canEditPermission: boolean;
    canDeletePermission: boolean;
    canViewPermission: boolean;
}) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Permissions', href: '/permission' }]}>
            <Head title="Permissions" />
            <AccessDirectory
                kind="permission"
                canCreate={canCreatePermission}
                canEdit={canEditPermission}
                canDelete={canDeletePermission}
                canView={canViewPermission}
            />
        </AppLayout>
    );
}
