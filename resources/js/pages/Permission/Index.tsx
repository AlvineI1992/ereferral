import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Manage from './Manage';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissions',
        href: '/permission',
    },
];
export default function Permission() {
    const [refreshKey, setRefreshKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const handleCreated = () => {
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
                <Manage onCreated={() => {}} />
            </div>
            {/*   <RolesManagement refreshKey={refreshKey} className="w-full" /> */}
        </AppLayout>
    );
}
