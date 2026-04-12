import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import Manage from './Manage';
import { type EmrPermissionProps } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Provider',
        href: '/emr',
    },
];

export default function Index({ canCreate, canEdit, canDelete, canView }: EmrPermissionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Providers" />

            <div className="lg:col-span-1">
                <Manage canCreate={canCreate} canEdit={canEdit} canDelete={canDelete} canView={canView} />
            </div>
        </AppLayout>
    );
}
