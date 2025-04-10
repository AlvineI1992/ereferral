import { useState } from 'react';
import Userlist from './UserList';
import UsersForm from './UsersForm';

const UsersManagement = () => {
    const [selectedUser, setSelectedUser] = useState(null); // State for the selected user
    const [refreshKey, setRefreshKey] = useState(0); // To refresh the user list after create/update

    // Handle the edit action and set the selected user
    const handleEdit = (user) => {
        setSelectedUser(user); // Set the user to be edited
    };

    // After a user is created or updated, reset the selected user to null
    const handleUserCreated = () => {
        setSelectedUser(null); // Reset the selected user after successful creation or update
        setRefreshKey((prev) => prev + 1); // Trigger refresh for the user list
    };

    // Handle cancel edit action
    const handleCancelEdit = () => {
        setSelectedUser(null); // Reset selected user when canceling the edit
    };

    return (
        <div className="roles-management">
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div className="lg:col-span-1">
                    {selectedUser ? (
                        <UsersForm
                            onCancel={handleCancelEdit} // Pass handleCancelEdit to the form
                            user={selectedUser}
                            onUserCreated={handleUserCreated}
                        />
                    ) : (
                        <UsersForm  onUserCreated={handleUserCreated} />
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
