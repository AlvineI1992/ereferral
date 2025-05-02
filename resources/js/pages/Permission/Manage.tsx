import React, { useState} from "react";
import { usePage } from '@inertiajs/react';
import Lists from "./List";  
import Form from "./Form"; 
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
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
       
        <div className="lg:col-span-1">
          {setSelected ? (
            <Form canCreate = {canCreate} onCancel={handleCancelEdit} perm={selected} onCreated={handleCreated} />
          ) : (
            <Form canCreate = {canCreate} onCreated={handleCreated} />
          )}
        </div>
        <div className="lg:col-span-3">
          <div className="mb-4">
          {canView && (
            <Lists canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={handleEdit} />
          )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Manage;
