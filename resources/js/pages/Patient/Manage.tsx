import { useState } from 'react';
import Lists from '../Emr/List';
import Form from './Form';

const Manage = () => {
    const [selectedId, setSelectedId] = useState(null); // ID of the selected user for editing
    const [refreshKey, setRefreshKey] = useState(0); // Used to trigger list refresh

    const handleEdit = (id) => {
        setSelectedId(id); // Set selected user ID for editing
    };

    const handleCreatedOrUpdated = () => {
        setSelectedId(null); // Reset selection
        setRefreshKey((prev) => prev + 1); // Refresh the list
    };

    const handleCancelEdit = () => {
        setSelectedId(null); // Clear selection when editing is canceled
    };

    return (
        <div className="roles-management">
            <div className="grid grid-cols-1 gap-4 lg:grid-cols-4">
                <div className="lg:col-span-1">
                    <Form
                        onCancel={selectedId ? handleCancelEdit : undefined}
                        emr={selectedId}
                        onCreated={handleCreatedOrUpdated}
                    />
                </div>
                <div className="lg:col-span-3">
                    <div className="mb-4">
                        <Lists refreshKey={refreshKey} onEdit={handleEdit} />
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Manage;
