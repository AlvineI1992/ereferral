import AppLayout from '@/layouts/app-layout';
import { Head } from "@inertiajs/react";
import RolesList from "./RolesList";
import Form from "./Form";
import { PlusCircle } from "lucide-react";
import { Link } from '@inertiajs/react';
import React, { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
];
export default function Roles() {
    const [refreshKey, setRefreshKey] = useState(0);
    const handleRoleCreated = () => {
      setRefreshKey((prev) => prev + 1); // triggers reload of RolesList
    };
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
        <Head title="Roles" />
        <div className="flex flex-col h-full rounded-xl p-2">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-2">
            <RolesList refreshKey={refreshKey} className="w-full" />
                <Form onRoleCreated={handleRoleCreated} className="w-full" />
                
            </div>
        </div>
    </AppLayout>);
}
