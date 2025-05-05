import React, { useEffect, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import Manage from './Manage';
import { BreadcrumbItem, PermissionProps } from './types';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Incoming Referral',
    href: '/incoming',
  },
];

export default function Incoming({
  canCreatePermission,
  canEditPermission,
  canDeletePermission,
  canViewPermission,
}: {
  canCreatePermission: boolean;
  canEditPermission: boolean;
  canDeletePermission: boolean;
  canViewPermission: boolean;
}) {
  const [refreshKey, setRefreshKey] = useState(0);
  const [loading, setLoading] = useState(true);

  const handleCreated = () => {
    setRefreshKey((prev) => prev + 1);
  };

  useEffect(() => {
    setLoading(true);
    const timeout = setTimeout(() => setLoading(false), 2000); // Simulated delay
    return () => clearTimeout(timeout);
  }, [refreshKey]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Incoming Referral Management" />
      <div className="p-2 w-full h-full">
        {loading ? (
          <div className="flex justify-center items-center h-64">
            <span className="text-lg font-semibold text-gray-500">Please wait...</span>
          </div>
        ) : (
          <Manage
            onCreated={handleCreated}
            canCreate={canCreatePermission}
            canEdit={canEditPermission}
            canDelete={canDeletePermission}
            canView={canViewPermission}
          />
        )}
      </div>
    </AppLayout>
  );
}
