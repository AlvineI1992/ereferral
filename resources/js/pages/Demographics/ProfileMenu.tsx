import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { User, Key, List } from 'lucide-react';

const sidebarNavItems: NavItem[] = [
  { title: 'Profile', href: '/emr/profile_form', icon: User },
  { title: 'Access', href: '/settings/password', icon: Key },
  { title: 'Activities', href: '/settings/appearance', icon: List },
];

export default function MenuProfile({ children }) {
  const { url } = usePage();
  const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

  return (
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
