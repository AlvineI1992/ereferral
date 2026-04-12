import Lists from "./List";  
import type { PermissionProps } from './types';

const Manage = ({
  canCreate,
  canEdit,
  canDelete,
  canView,
}: PermissionProps) => {
  return (
    <div className="roles-management">
      {canView ? (
        <Lists canCreate={canCreate} canEdit={canEdit} canDelete={canDelete} canView={canView} refreshKey={0} />
      ) : (
        <div className="rounded-lg border p-4 text-sm text-slate-600">
          You do not have permission to view incoming referrals.
        </div>
      )}
    </div>
  );
};

export default Manage;
