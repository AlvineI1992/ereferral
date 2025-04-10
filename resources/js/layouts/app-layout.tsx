import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
/* import AppLayoutTemplate from '@/layouts/app/app-header-layout'; */
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
  <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
    {/* Full screen background */}
    <div className="absolute inset-0 h-screen w-screen bg-[radial-gradient(green_1px,transparent_1px)] [background-size:16px_16px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)] dark:bg-[radial-gradient(#1f2937_1px,transparent_1px)]"></div>

    {/* Main content, stacked above the background */}
    <div className="relative z-10">
      {children}
    </div>
  </AppLayoutTemplate>
);
