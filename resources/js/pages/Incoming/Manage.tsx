import React, { useState} from "react";
import { usePage } from '@inertiajs/react';
import Lists from "./List";  
import { PermissionProps } from './types';

const Manage = ({
  canCreate,
  canEdit,
  canDelete,
  canView,
}: PermissionProps) => {
  const [selected, setSelected] = useState(null); 
  const [refreshKey, setRefreshKey] = useState(0); 

 
  const handleEdit = (perm) => {
    setSelected(perm); // Set the role to be edited
  };


  const handleCreated = () => {
    setSelected(null);
    setRefreshKey(prev => prev + 1); 
  };

 const handleCancelEdit = () => {
  setSelected(null);
};

  return (
    <div className="roles-management">
          {/* {canView && (
            <Lists canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={handleEdit} />
          )} */}
          <Lists canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={handleEdit} />
      
    </div>
  );
};

export default Manage;
