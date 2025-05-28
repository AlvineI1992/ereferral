import AppLayout from '@/layouts/app-layout';
import { useEffect, useState } from 'react';
import { Head,usePage } from '@inertiajs/react';
import axios from 'axios';

import RolesInfo from '../Roles/RolesInfo';
import RolesListAssign from '../Roles/RolesListAssign';
import Menu from '../Roles/Menu';
import type { BreadcrumbItem } from '@/types';
import { LoaderCircle, Save, User, X } from "lucide-react";

import { Separator } from "@/components/ui/separator";

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Roles', href: '/roles' },
  { title: 'Permission Assignment', href: '' },
];

type ProfileLayoutProps = {
  id: string;
  is_include:boolean;
};

type ProfileData = {
  id: number;
  name: string;
  guard_name: string;
  permissions: string[];
  [key: string]: any;
};

export default function ProfileLayout({ id ,is_include}: ProfileLayoutProps) {
  const { url } = usePage(); // 🔥 Get the full current route URL
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  useEffect(() => {
    const fetchProfile = async () => {
      setLoading(true);
      setError(null);
      try {
        const res = await axios.get(`/roles/info/${id}`);
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


  
  const handleSaveSuccess = () => {
    setRefreshKey((prev) => prev + 1); 
  };

  const isIncludePage = url.includes(`/roles/assign/${id}`);
  const isExcludePage = url.includes(`/roles/assigned/${id}`);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>

      {loading && (
        <div className="text-gray-500 text-center py-4">Loading profile...</div>
      )}

      {!loading && error && (
        <div className="text-red-500 text-center py-4">{error}</div>
      )}
      <Head title="Role Management" />
      <div className="flex items-center ml-3 mr-3  mb-5 mt-2">
      <User size={18} />
        <h1 className="text-xl">Permission Assignment</h1>
      </div>

      {!loading && profile && (

        <div className="flex flex-col md:flex-row ">
          <div className="w-full md:w-[15%]">
            <RolesInfo profile={profile}  />
            <Separator className='mt-2 mr-1 ml-1 mb-3'/>
            <Menu id={id}/>
          </div>
          <div className="hidden md:block bg-gray-200 w-px mx-2" />
        
          <div className="w-full md:w-[85%]">
            {isIncludePage && (
              <RolesListAssign
                key={url}
                refreshKey={url}
                id={parseInt(id)}
                is_include={true}
                onSave={handleSaveSuccess}
              />
            )}

            {isExcludePage && (
              <RolesListAssign
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
