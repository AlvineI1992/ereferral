import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';
import type { BreadcrumbItem, PermissionProps } from './types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Incoming Referral',
    href: '/incoming',
  },
];

export default function Incoming({
  canCreate,
  canEdit,
  canDelete,
  canView,
}: PermissionProps) {
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Incoming Referral Management" />
      <div className="flex flex-1 flex-col p-4 md:p-6">
        <Manage
          canCreate={canCreate}
          canEdit={canEdit}
          canDelete={canDelete}
          canView={canView}
        />
      </div>
    </AppLayout>
  );
}
