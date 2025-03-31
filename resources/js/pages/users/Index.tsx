import AppLayout from '@/layouts/app-layout';
import { Head } from "@inertiajs/react";
import UserList from "./UserList";
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User accounts',
        href: '/user accounts',
    },
];
export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <UserList />
            </div>
        </AppLayout>
    );
}
