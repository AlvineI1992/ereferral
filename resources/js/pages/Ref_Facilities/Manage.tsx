import { useState } from 'react';
import Lists from './List';
import Form from './Form';
import { Separator } from '@/components/ui/separator';
import { PermissionProps } from './types';


const Manage = ({
    canCreate,
    canEdit,
    canDelete,
    canView,
}: PermissionProps) => {
    const [selectedId, setSelectedId] = useState<number | null>(null); // ID of the selected user for editing
    const [refreshKey, setRefreshKey] = useState(0); // Used to trigger list refresh

    const handleEdit = (id: number) => {
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
            <div className="grid grid-cols-1 gap-1 lg:grid-cols-4">
                <div className="lg:col-span-1">
                    {/* Conditionally render Form component based on permission */}
                    <Form
                        canCreate = {canCreate} onCancel={selectedId ? handleCancelEdit : undefined}
                        emr={selectedId}
                        onCreated={handleCreatedOrUpdated}
                    />
                </div>

                <div className="lg:col-span-3">
                    <div className="mb-1">
                        {/* Conditionally render Lists component based on permission */}
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
