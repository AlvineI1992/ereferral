import React, { useState } from "react";
import RolesList from "./RolesList";  // The component that shows the list of roles
import RolesForm from "./RolesForm";  // The form for creating or editing roles
import { PermissionProps } from './types';
import axios from "axios";


const RolesManagement = ({
  canCreate,
  canEdit,
  canDelete,
  canView,
  canAssign
}: PermissionProps) => {
  const [selectedRole, setSelectedRole] = useState(null); // State for the selected role
  const [refreshKey, setRefreshKey] = useState(0); // To refresh the role list after create/update


  const handleEdit = async (roleId) => {
    try {
      const response = await axios.get(`/roles/info/${roleId}`);
      setSelectedRole(response.data);
    } catch (error) {
      console.error("Failed to fetch role info:", error);
    }
  };

  // After a role is created or updated, reset the selected role to null
  const handleRoleCreated = () => {
    setSelectedRole(null); // Reset the selected role after successful creation or update
    setRefreshKey(prev => prev + 1); // Trigger refresh for the role list
  };
 // Handle cancel edit action
 const handleCancelEdit = () => {
  setSelectedRole(null); // Reset selected role when canceling the edit
};
  return (
    <div className="roles-management">
      <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
        {/* Render the Roles List and pass the handleEdit function to it */}

        {/* Conditionally render the Roles Form based on whether a role is selected for editing */}
        <div className="lg:col-span-1">
          {selectedRole ? (
            <RolesForm canCreate = {canCreate} onCancel={handleCancelEdit} role={selectedRole} onRoleCreated={handleRoleCreated} />
          ) : (
            <RolesForm canCreate = {canCreate} onRoleCreated={handleRoleCreated} />
          )}
        </div>
        <div className="lg:col-span-3">
          <div className="mb-4">
           
          {canView && (
            <RolesList canEdit={canEdit} canDelete={canDelete} canAssign={canAssign}  refreshKey={refreshKey} onEdit={handleEdit} />
          )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default RolesManagement;
