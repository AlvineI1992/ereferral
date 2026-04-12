import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';

const breadcrumbs = [
    {
        title: 'Demographics',
        href: '/demographics',
    },
];

export default function DemographicsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Demographics" />
            <div className="lg:col-span-1">
                <Manage />
            </div>
        </AppLayout>
    );
}
