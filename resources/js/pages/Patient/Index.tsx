import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';
import { type PatientPermissionProps } from './types';

const breadcrumbs = [
    {
        title: 'Patient Registry',
        href: '/patient_registry',
    },
];

export default function PatientIndex(props: PatientPermissionProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Patient Registry" />
            <div className="lg:col-span-1">
                <Manage {...props} />
            </div>
        </AppLayout>
    );
}
