import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Lists from './List';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Patient',
        href: '/patient-list',
    },
];
export default function Index() {
    
    const [loading, setLoading] = useState(true);

    const handleCreated = () => {
        setRefreshKey((prev) => prev + 1); 
    };

    const [selectedId, setSelectedId] = useState(null); // ID of the selected user for editing
    const [refreshKey, setRefreshKey] = useState(0); // Used to trigger list refresh

    const handleEdit = (id) => {
        setSelectedId(id); // Set selected user ID for editing
    };

    useEffect(() => {
        setLoading(true);
        setTimeout(() => {
            setLoading(false);
        }, 2000); 
    }, [refreshKey]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="lg:col-span-1">
            <Lists refreshKey={refreshKey} onEdit={handleEdit} />
            </div>
        </AppLayout>
    );
}
