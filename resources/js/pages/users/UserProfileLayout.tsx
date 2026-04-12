import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { User } from 'lucide-react';
import { useEffect, useState } from 'react';
import Menu from './Menu';
import Profileinfo from './UsersInfo';
import UsersListAssign from './UsersListAssign';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'Role Assignment', href: '' },
];

type ProfileLayoutProps = {
    id: string;
    is_include: boolean;
};

type ProfileData = {
    id: number;
    name: string;
    email: string;
    primary_role?: string | null;
    role?: string | null;
    status?: string;
    [key: string]: any;
};

export default function ProfileLayout({ id, is_include }: ProfileLayoutProps) {
    const { url } = usePage();
    const [profile, setProfile] = useState<ProfileData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);
    const [error, setError] = useState<string | null>(null);

    // Fetch profile data
    useEffect(() => {
        const fetchProfile = async () => {
            setLoading(true);
            setError(null);
            try {
                const res = await axios.get(`/users/info/${id}`);
                setProfile(res.data);
            } catch (err) {
                console.error('Failed to fetch profile:', err);
                setError('Failed to load profile data.');
                setProfile(null);
            } finally {
                setLoading(false);
            }
        };

        fetchProfile();
    }, [id]);

    // Handle save success
    const handleSaveSuccess = () => {
        // Triggering component refresh by using `url` as the key.
    };

    const isIncludePage = url.includes(`/users/assign-roles/${id}`);
    const isExcludePage = url.includes(`/users/assigned-roles/${id}`);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            {loading && <div className="py-4 text-center">Loading profile...</div>}
            {!loading && error && <div className="py-4 text-center text-red-500">{error}</div>}
            <Head title="Role Management" />

            <div className="mt-2 mr-3 mb-3 ml-3 flex items-center">
                <User size={18} />
                <h1 className="text-lg font-semibold">Role Assignment</h1>
            </div>

            {!loading && profile && (
                <div className="flex flex-col md:flex-row">
                    <div className="w-full md:w-[13%]">
                        <Profileinfo profile={profile} />
                        <Separator className="mt-2 mr-1 mb-3 ml-1" />
                        <Menu id={id} />
                    </div>

                    <div className="w-full md:w-[87%]">
                        {isIncludePage && (
                            <UsersListAssign key={url} refreshKey={url} id={parseInt(id)} is_include={true} onSave={handleSaveSuccess} />
                        )}
                        {isExcludePage && (
                            <UsersListAssign key={url} refreshKey={url} id={parseInt(id)} is_include={false} onSave={handleSaveSuccess} />
                        )}
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
