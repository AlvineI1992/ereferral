import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { PanelLeftClose, PanelLeftOpen, Plus, ShieldAlert } from 'lucide-react';
import { useState } from 'react';
import { type PermissionProps, type UserRecord } from './types';
import UserList from './UserList';
import UsersForm from './usersForm';

const UsersManagement = ({ canCreate, canEdit, canDelete, canView, canAssign }: PermissionProps) => {
    const [selectedUser, setSelectedUser] = useState<UserRecord | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [isFormOpen, setIsFormOpen] = useState(canCreate);

    const hasFormAccess = canCreate || canEdit;

    const handleEdit = (user: UserRecord) => {
        if (!canEdit) {
            return;
        }

        setSelectedUser(user);
        setIsFormOpen(true);
    };

    const handleUserSaved = () => {
        setSelectedUser(null);
        setRefreshKey((prev) => prev + 1);

        if (!canCreate) {
            setIsFormOpen(false);
        }
    };

    const handleCancelEdit = () => {
        setSelectedUser(null);

        if (!canCreate) {
            setIsFormOpen(false);
        }
    };

    return (
        <div className="space-y-4 p-4">
            <section className="border-primary/15 from-primary/10 via-background to-background rounded-2xl border bg-gradient-to-r p-5 shadow-sm">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-primary text-sm font-medium">Identity And Access</p>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Manage user accounts</h1>
                            <p className="text-muted-foreground text-sm">
                                Create staff accounts, update access scope, and manage who can sign in to the system.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Badge variant={canCreate ? 'default' : 'outline'}>Create</Badge>
                        <Badge variant={canEdit ? 'default' : 'outline'}>Edit</Badge>
                        <Badge variant={canDelete ? 'default' : 'outline'}>Delete</Badge>
                        <Badge variant={canView ? 'default' : 'outline'}>View</Badge>
                        <Badge variant={canAssign ? 'default' : 'outline'}>Assign Roles</Badge>
                    </div>
                </div>
            </section>

            <div className={cn('grid gap-4', hasFormAccess && isFormOpen ? 'xl:grid-cols-[380px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {hasFormAccess && isFormOpen && (
                    <UsersForm
                        canCreate={canCreate}
                        canEdit={canEdit}
                        onCancel={handleCancelEdit}
                        onUserCreated={handleUserSaved}
                        user={selectedUser}
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
                                    title={isFormOpen ? 'Hide user form' : 'Show user form'}
                                >
                                    {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                                </Button>

                                <div>
                                    <p className="text-sm font-medium">{selectedUser ? `Editing ${selectedUser.name}` : 'User form'}</p>
                                    <p className="text-muted-foreground text-xs">
                                        {selectedUser
                                            ? 'Update profile, access scope, or sign-in status for the selected account.'
                                            : canCreate
                                              ? 'Open the form to create a new user account.'
                                              : 'Select a user from the list to start editing.'}
                                    </p>
                                </div>
                            </div>

                            {canCreate && (
                                <Button
                                    type="button"
                                    onClick={() => {
                                        setSelectedUser(null);
                                        setIsFormOpen(true);
                                    }}
                                >
                                    <Plus className="size-4" />
                                    New User
                                </Button>
                            )}
                        </div>
                    )}

                    {canView ? (
                        <UserList canAssign={canAssign} canDelete={canDelete} canEdit={canEdit} onEdit={handleEdit} refreshKey={refreshKey} />
                    ) : (
                        <Card>
                            <CardContent className="flex items-start gap-3 p-6">
                                <ShieldAlert className="text-muted-foreground mt-0.5 size-5" />
                                <div className="space-y-1">
                                    <p className="font-medium">User list is unavailable</p>
                                    <p className="text-muted-foreground text-sm">
                                        Your account can open the user workspace, but it does not include permission to view user records.
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

export default UsersManagement;
