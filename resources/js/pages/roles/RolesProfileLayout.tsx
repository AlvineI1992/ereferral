// ProfileLayout.tsx
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem, type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { User, Key, List } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import RolesInfo from '../Roles/RolesInfo';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Roles', href: '/roles' },
  { title: 'Assign Permission', href: '' },
];

type ProfileLayoutProps = {
  id: string;
};


export default function ProfileLayout({ id}: ProfileLayoutProps) {
  const [profile, setProfile] = useState(null);

  useEffect(() => {
    axios.get(`/api/roles/info/${id}`).then((res) => {
      setProfile(res.data);
    });
  }, [id]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      {profile && <RolesInfo profile={profile} />}
     

    </AppLayout>
  );
}