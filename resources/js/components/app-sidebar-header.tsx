import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { MapPin } from 'lucide-react';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    const { auth } = usePage<SharedData>().props;

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            {auth.user.access_label && (
                <div className="ml-auto flex min-w-0 items-center gap-1.5 rounded-full border bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700">
                    <MapPin className="size-3.5 shrink-0 text-teal-700" />
                    <span className="max-w-40 truncate sm:max-w-72">{auth.user.access_label}</span>
                </div>
            )}
        </header>
    );
}
