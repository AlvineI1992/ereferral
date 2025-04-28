// ProfileLayout.tsx
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem, type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { User, Key, List } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import RolesInfo from './RolesInfo';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Provider', href: '/emr' },
  { title: 'Profile', href: '' },
];

type ProfileLayoutProps = {
  id: string;
  children: React.ReactNode;
};

const sidebarNavItems: NavItem[] = [
  { title: 'Profile', href: '/emr/profile_form', icon: User },
  { title: 'Access', href: '/settings/password', icon: Key },
  { title: 'Activities', href: '/settings/appearance', icon: List },
];

export default function ProfileLayout({ id, children }: ProfileLayoutProps) {
  const [profile, setProfile] = useState(null);

  useEffect(() => {
    axios.get(`/api/emr/info/${id}`).then((res) => {
      setProfile(res.data);
    });
  }, [id]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      {profile && <RolesInfo profile={profile} />}
      <div className="text-sm ml-4">
        <Heading title="Menu" description="" />
      </div>

    </AppLayout>
  );
}