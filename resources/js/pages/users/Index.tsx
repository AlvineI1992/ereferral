import AppLayout from '@/layouts/app-layout';
import { Head } from "@inertiajs/react";
import UserList from "./UserList";
import { Link } from '@inertiajs/react';
import { PlusCircle } from "lucide-react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'User accounts',
        href: '/user accounts',
    },
];
export default function UsersList() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div className="flex  items-right">
            <Link
                href={route('users.create')}
                  className="border border-green-500 bg-white text-green-500 px-2 py-1 rounded flex items-center gap-1 text-sm hover:bg-green-500 hover:text-white"
            >
                <PlusCircle size={16} /> Add User
            </Link>

                </div>
                <UserList />
            </div>
        </AppLayout>
    );
}
