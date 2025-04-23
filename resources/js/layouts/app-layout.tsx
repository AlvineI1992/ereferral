import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
/* import AppLayoutTemplate from '@/layouts/app/app-header-layout'; */
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';

interface AppLayoutProps {
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => (
  <>
    <Toaster richColors position="top-right" /> {/* You can adjust the position if needed */}
    <AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
      {/* Main content, stacked above the background */}
      
      <div className="relative z-10">
        {children}
      </div>
    </AppLayoutTemplate>
  </>
);
