import AppLayout from '@/layouts/app-layout';
import { Head } from "@inertiajs/react";
import RolesList from "./RolesList";
import Form from "./Form";
import { PlusCircle } from "lucide-react";
import { Link } from '@inertiajs/react';

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
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <Form />
                    <RolesList />
                </div>
            </div>
        </AppLayout>
    );
}
