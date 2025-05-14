import { useEffect, useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import AppLayout from '@/layouts/app-layout';
import Profileinfo from '../Users/UsersInfo';
import UsersListAssign from '../Users/UsersListAssign';
import Menu from '../Users/Menu';
import type { BreadcrumbItem } from '@/types';
import { LoaderCircle, Save, User, X } from 'lucide-react';
import { Separator } from '@/components/ui/separator';

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
  guard_name: string;
  permissions: string[];
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
      {loading && <div className="text-center py-4">Loading profile...</div>}
      {!loading && error && <div className="text-red-500 text-center py-4">{error}</div>}
      <Head title="Role Management" />

      <div className="flex items-center ml-3 mr-3 mb-3 mt-2">
        <User size={18} />
        <h1 className="text-lg font-semibold">Role Assignment</h1>
      </div>

      {!loading && profile && (
        <div className="flex flex-col md:flex-row">
          <div className="w-full md:w-[13%]">
            <Profileinfo profile={profile} />
            <Separator className="mt-2 mr-1 ml-1 mb-3" />
            <Menu id={id} />
          </div>


          <div className="w-full md:w-[87%]">
            {isIncludePage && (
              <UsersListAssign
                key={url}
                refreshKey={url}
                id={parseInt(id)}
                is_include={true}
                onSave={handleSaveSuccess}
              />
            )}
            {isExcludePage && (
              <UsersListAssign
                key={url}
                refreshKey={url}
                id={parseInt(id)}
                is_include={false}
                onSave={handleSaveSuccess}
              />
            )}
          
           
          </div>
        </div>
      )}
    </AppLayout>
  );
}
