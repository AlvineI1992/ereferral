import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import UsersManagement from './UsersManagement';
import { type PermissionProps } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User Accounts',
        href: '/users',
    },
];

export default function UsersIndex({ canCreate, canEdit, canDelete, canView, canAssign }: PermissionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="lg:col-span-1">
                <UsersManagement canAssign={canAssign} canCreate={canCreate} canDelete={canDelete} canEdit={canEdit} canView={canView} />
            </div>
        </AppLayout>
    );
}
