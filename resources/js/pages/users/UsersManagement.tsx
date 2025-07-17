import axios from 'axios';
import { useState } from 'react';
import Userlist from './UserList';
import UsersForm from './UsersForm';
import { PermissionProps } from './types';

const UsersManagement = ({
    canCreate,
    canEdit,
    canDelete,
    canView,
    canAssign
  }: PermissionProps) => {
    const [selectedUser, setSelectedUser] = useState(null); 
    const [refreshKey, setRefreshKey] = useState(0); 

    const [selectedProvider, setSelectedProvider] = useState(null);
    const [selectedRegion, setSelectedRegion] = useState(null);
    const [selectedHospital, setSelectedHospital] = useState(null);

   
    const handleEdit = async (user: null) => {
        try {
            const response = await axios.get(`/users/info/${user}`);
            setSelectedUser(response.data);
        } catch (error) {
            console.error('Failed to fetch role info:', error);
        }
    };

    
    const handleUserCreated = () => {
        setSelectedUser(null); 
        setRefreshKey((prev) => prev + 1); 
    };

  
    const handleCancelEdit = () => {
        setSelectedUser(null); 
        setSelectedProvider('');
        setSelectedRegion('');
        setSelectedHospital('');
    };

    return (
        <div className="roles-management">
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div className="lg:col-span-1">
                    {selectedUser ? (
                        <UsersForm
                            onCancel={handleCancelEdit}
                            user={selectedUser}
                            onUserCreated={handleUserCreated}
                        />
                    ) : (
                        <UsersForm   onCancel={''}
                        user={''}  onUserCreated={handleUserCreated} />
                    )}
                </div>
                <div className="lg:col-span-3">
                    <div className="mb-4">
                        <Userlist refreshKey={refreshKey} onEdit={handleEdit} />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default UsersManagement;
