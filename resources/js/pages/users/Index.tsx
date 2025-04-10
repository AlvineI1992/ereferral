import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import UsersManagement from './UsersManagement';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User accounts',
        href: '/user accounts',
    },
];
export default function UsersList() {
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
            <Head title="Users" />
            <div className="lg:col-span-1">
                <UsersManagement onRoleCreated={() => {}} />
            </div>
        </AppLayout>
    );
}
