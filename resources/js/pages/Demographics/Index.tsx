import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Manage from './Manage';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Facilities',
        href: '/facilities',
    },
];
export default function list() {
    const [refreshKey, setRefreshKey] = useState(0);
    const [loading, setLoading] = useState(true);
    const handleCreated = () => {
        setRefreshKey((prev) => prev + 1); 
    };
    useEffect(() => {
        setLoading(true);
        setTimeout(() => {
            setLoading(false);
        }, 2000); // Simulate loading for 2 seconds
    }, [refreshKey]);
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="lg:col-span-1">
                <Manage  />
            </div>
        </AppLayout>
    );
}
