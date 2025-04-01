import AppLayout from '@/layouts/app-layout';
import { Head } from "@inertiajs/react";
import RolesList from "./RolesList";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
];
export default function Roles() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <RolesList />
            </div>
        </AppLayout>
    );
}
