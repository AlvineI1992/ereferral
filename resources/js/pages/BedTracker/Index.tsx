import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';
import { type BedTrackerPermissionProps } from './types';

const breadcrumbs = [
    {
        title: 'Bed Tracker',
        href: '/bed_tracker',
    },
];

export default function BedTrackerIndex(props: BedTrackerPermissionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bed Tracker" />
            <div className="lg:col-span-1">
                <Manage {...props} />
            </div>
        </AppLayout>
    );
}
