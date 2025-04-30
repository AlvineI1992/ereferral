// ProfileLayout.tsx
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem, type BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { User, Key, List } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import Profileinfo from './Profileinfo';
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
      {profile && <Profileinfo profile={profile} />}
      <div className="text-sm ml-4">
        <Heading title="Menu" description="" />
      </div>

      <div className="flex flex-col space-y-4 lg:flex-row lg:space-y-0 lg:space-x-12 ml-3">
        <aside className="w-full max-w-xl lg:w-45">
          <nav className="flex flex-col space-y-1 space-x-0">
            {sidebarNavItems.map((item) => {
              const Icon = item.icon;
              return (
                <Button
                  key={item.href}
                  size="sm"
                  variant="ghost"
                  asChild
                  className={cn('w-full justify-start gap-2')}
                >
                  <Link href={`${item.href}/${id}`} preserveState preserveScroll>
                    <span className="flex items-center gap-2">
                      {Icon && <Icon className="h-4 w-4" />}
                      {item.title}
                    </span>
                  </Link>
                </Button>
              );
            })}
          </nav>
        </aside>

        <div className="flex-1 md:max-w-2xl">
          <section className="max-w-xl space-y-12">{children}</section>
        </div>
      </div>
    </AppLayout>
  );
}