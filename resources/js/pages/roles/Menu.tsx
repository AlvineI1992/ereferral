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
    { title: 'Access', href: `/roles/assign/${id}`, icon: Check },
    { title: 'Revoke', href:`/roles/assigned/${id}`, icon: Key },
    
  ];

  return (
    <div className="flex flex-col space-y-4 lg:flex-row lg:space-y-0 lg:space-x-12 ml-3">
    
      <aside className="w-full max-w-lg lg:w-55">
      <h6 className="text-lg mb-2"> Menu</h6>
        <nav className="flex flex-col space-y-2 space-x-0">
          {sidebarNavItems.map((item) => {
            const Icon = item.icon;
            return (
              <Button
                key={item.href}
                size="sm"
                variant="ghost"
                asChild
                className={cn('w-full justify-start gap-1 mb-2', {
                  'bg-muted': currentPath === item.href,
                })}
              >
                <Link href={item.href} preserveScroll preserveState>
                  <span className="flex  gap-2">
                    {Icon && <Icon className="h-2 w-2" />}
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
