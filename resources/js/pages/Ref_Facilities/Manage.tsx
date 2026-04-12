import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { PanelLeftClose, PanelLeftOpen, Plus, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import Form from './Form';
import Lists from './List';
import { type FacilityPermissionProps, type FacilityRecord } from './types';

const Manage = ({ canCreate, canEdit, canDelete, canView }: FacilityPermissionProps) => {
    const [selectedFacility, setSelectedFacility] = useState<FacilityRecord | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const hasFormAccess = canCreate || canEdit;
    const [isFormOpen, setIsFormOpen] = useState(hasFormAccess && canCreate);

    const handleEdit = (facility: FacilityRecord) => {
        if (!canEdit) {
            return;
        }

        setSelectedFacility(facility);
        setIsFormOpen(true);
    };

    const handleCreatedOrUpdated = () => {
        setSelectedFacility(null);
        setRefreshKey((prev) => prev + 1);

        if (!canCreate) {
            setIsFormOpen(false);
        }
    };

    const handleCancelEdit = () => {
        setSelectedFacility(null);

        if (!canCreate) {
            setIsFormOpen(false);
        }
    };

    return (
        <div className="space-y-4 p-4">
            <section className="border-primary/15 from-primary/10 via-background to-background rounded-2xl border bg-gradient-to-r p-5 shadow-sm">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-primary text-sm font-medium">Facilities Directory</p>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Manage healthcare facilities</h1>
                            <p className="text-muted-foreground text-sm">
                                Maintain facility records, keep statuses up to date, and prepare entries for referral routing.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Badge variant={canCreate ? 'default' : 'outline'}>Create</Badge>
                        <Badge variant={canEdit ? 'default' : 'outline'}>Edit</Badge>
                        <Badge variant={canDelete ? 'default' : 'outline'}>Delete</Badge>
                        <Badge variant={canView ? 'default' : 'outline'}>View</Badge>
                    </div>
                </div>
            </section>

            <div className={cn('grid gap-4', hasFormAccess && isFormOpen ? 'xl:grid-cols-[390px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {hasFormAccess && isFormOpen && (
                    <Form
                        canCreate={canCreate}
                        canEdit={canEdit}
                        onCancel={handleCancelEdit}
                        formval={selectedFacility}
                        onCreated={handleCreatedOrUpdated}
                    />
                )}

                <div className="space-y-4">
                    {hasFormAccess && (
                        <div className="bg-background flex flex-col gap-3 rounded-xl border p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setIsFormOpen((prev) => !prev)}
                                    title={isFormOpen ? 'Hide facility form' : 'Show facility form'}
                                >
                                    {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                                </Button>

                                <div>
                                    <p className="text-sm font-medium">
                                        {selectedFacility ? `Editing ${selectedFacility.facility_name}` : 'Facility form'}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {selectedFacility
                                            ? 'Save updates for the selected facility record.'
                                            : canCreate
                                              ? 'Open the form to register a new facility.'
                                              : 'Select a row to edit an existing facility.'}
                                    </p>
                                </div>
                            </div>

                            {canCreate && (
                                <Button
                                    type="button"
                                    onClick={() => {
                                        setSelectedFacility(null);
                                        setIsFormOpen(true);
                                    }}
                                >
                                    <Plus className="size-4" />
                                    New Facility
                                </Button>
                            )}
                        </div>
                    )}

                    {canView ? (
                        <Lists canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={handleEdit} />
                    ) : (
                        <Card>
                            <CardContent className="flex items-start gap-3 p-6">
                                <ShieldAlert className="text-muted-foreground mt-0.5 size-5" />
                                <div className="space-y-1">
                                    <p className="font-medium">Facility list is unavailable</p>
                                    <p className="text-muted-foreground text-sm">
                                        Your account can open this workspace, but it does not have permission to view facility records.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Manage;
