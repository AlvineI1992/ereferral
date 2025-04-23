import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';
import TabsAction from './Tabs';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'; // ShadCN Avatar
import { Separator } from "@/components/ui/separator"
type BreadcrumbItem = {
    title: string;
    href: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Provider', href: '/emr' },
    { title: 'Profile', href: '/emr/profile' },
];

type ProfileData = {
    name: string;
    role: string;
    email: string;
    phone: string;
    location: string;
};

type Props = {
    id: number;
};

const ProfileView = ({ id }: Props) => {
    const [profile, setProfile] = useState<ProfileData | null>(null);
    const [loading, setLoading] = useState<boolean>(true);

    useEffect(() => {
        axios.get(`/api/emr/info/${id}`)
            .then(response => {
                setProfile(response.data);
                setLoading(false);
            })
            .catch(error => {
                console.error('Error fetching profile:', error);
                setLoading(false);
            });
    }, [id]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile" />
            <div className="flex">
                {/* Left Section - Profile Information */}
                <div className="w-[300px] p-4">
                    {loading ? (
                        <p>Loading...</p>
                    ) : profile ? (
                        <>
                            <div className="flex items-center gap-4 mb-6">
                                {/* Avatar */}
                                <Avatar className="w-16 h-16">
                                    <AvatarImage src={profile.avatar || '/default-avatar.jpg'} />
                                    <AvatarFallback>{profile.emr_name?.charAt(0)}</AvatarFallback>
                                </Avatar>

                                {/* Profile Information */}
                                <div>
                                    <h2 className="text-xl font-semibold">{profile.emr_name}</h2>
                                    <p className="text-gray-500 text-sm">{profile.role}</p>
                                </div>
                            </div>

                            <div className="divide-y divide-gray-200">
                                <div className="py-2">
                                    <p className="text-gray-500 text-sm">Email</p>
                                    <p className="text-sm">{profile.email}</p>
                                </div>
                                <div className="py-2">
                                    <p className="text-gray-500 text-sm">Phone</p>
                                    <p className="text-sm">{profile.phone}</p>
                                </div>
                                <div className="py-2">
                                    <p className="text-gray-500 text-sm">Location</p>
                                    <p className="text-sm">{profile.location}</p>
                                </div>
                            </div>
                        </>
                    ) : (
                        <p>Profile not found.</p>
                    )}
                </div>
           
                {/* Right Section - TabsAction */}
                <div className="mt-8 ml-8 flex-1">
                    
                    <TabsAction />
                </div>
            </div>
        </AppLayout>
    );
};

export default ProfileView;
