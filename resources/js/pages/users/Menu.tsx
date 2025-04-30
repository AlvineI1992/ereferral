import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Check, Key, List } from 'lucide-react';
import React from 'react';

type MenuProps = {
  id: number | string;
  children: React.ReactNode;
};

export default function Menu({ id, children }: MenuProps) {
  const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

  const sidebarNavItems: NavItem[] = [
    { title: 'Available  Roles', href: `/users/assign-roles/${id}`, icon: Check },
    { title: 'Access Roles', href:`/users/assigned-roles/${id}`, icon: Key },
    
  ];

  return (
    <div className="flex flex-col space-y-4 lg:flex-row lg:space-y-0 lg:space-x-12 ml-3">
    
      <aside className="w-full max-w-xl lg:w-45">
      <h6 className="text-lg font-bold text-gray-800"> Menu</h6>
        <nav className="flex flex-col space-y-1 space-x-0">
          {sidebarNavItems.map((item) => {
            const Icon = item.icon;
            return (
              <Button
                key={item.href}
                size="sm"
                variant="ghost"
                asChild
                className={cn('w-full justify-start gap-2', {
                  'bg-muted': currentPath === item.href,
                })}
              >
                <Link href={item.href} preserveScroll preserveState>
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
  );
}
