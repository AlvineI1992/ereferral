import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';

const breadcrumbs = [
    {
        title: 'Religions',
        href: '/religions',
    },
];

export default function ReligionIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Religions" />
            <div className="lg:col-span-1">
                <Manage />
            </div>
        </AppLayout>
    );
}
