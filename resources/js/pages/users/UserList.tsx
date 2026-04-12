import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRight, Pencil, Search, Trash2 } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { toast } from 'sonner';
import Swal from 'sweetalert2';
import { type UserRecord } from './types';

type Props = {
    refreshKey: number;
    onEdit: (user: UserRecord) => void;
    canDelete: boolean;
    canEdit: boolean;
    canAssign: boolean;
};

const PAGE_SIZE = 10;

const UserList = ({ canAssign, canDelete, canEdit, refreshKey, onEdit }: Props) => {
    const {
        props: { auth },
    } = usePage<SharedData>();

    const [rows, setRows] = useState<UserRecord[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearchTerm = useDeferredValue(searchTerm);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalRows, setTotalRows] = useState(0);

    const currentUserId = auth.user.id;

    const fetchData = async (pageNumber = 1, search = '') => {
        setLoading(true);

        try {
            const response = await axios.get('/users/list', {
                params: {
                    page: pageNumber,
                    search,
                },
            });

            setRows(response.data.data);
            setTotalRows(response.data.total);
        } catch (error) {
            console.error('Error fetching users:', error);
            toast.error('Unable to load user accounts right now.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        setPage(1);
    }, [searchTerm]);

    useEffect(() => {
        void fetchData(page, deferredSearchTerm.trim());
    }, [deferredSearchTerm, page, refreshKey]);

    const handleDelete = async (user: UserRecord) => {
        if (user.id === currentUserId) {
            toast.error('You cannot delete your own account while you are signed in.');
            return;
        }

        const result = await Swal.fire({
            title: `Delete ${user.name}?`,
            text: 'This user will lose access to the application.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete user',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/users/delete/${user.id}`);
            toast.success(`${user.name} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error: any) {
            console.error('Error deleting user:', error);

            const message = error.response?.data?.message ?? 'Something went wrong while deleting this user.';

            Swal.fire('Unable to delete', message, 'error');
        }
    };

    const handleRoles = (id: number) => {
        router.visit(`/users/assign-roles/${id}`);
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / PAGE_SIZE));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
    const recordEnd = Math.min(page * PAGE_SIZE, totalRows);

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 gap-4 border-b sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-1">
                    <CardTitle className="text-base">User Accounts</CardTitle>
                    <CardDescription>Review active accounts, update their access scope, or jump into role assignment.</CardDescription>
                </div>

                <div className="relative w-full sm:w-72">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        type="search"
                        placeholder="Search name, email, type, or status"
                        value={searchTerm}
                        onChange={(event) => setSearchTerm(event.target.value)}
                        className="pl-9"
                    />
                </div>
            </CardHeader>

            <CardContent className="space-y-4 p-0">
                {loading ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm">
                        <div className="border-primary size-5 animate-spin rounded-full border-2 border-t-transparent" />
                        Loading users...
                    </div>
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>User</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Access</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {rows.length > 0 ? (
                                    rows.map((row) => {
                                        const isCurrentUser = row.id === currentUserId;

                                        return (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p className="font-medium">{row.name}</p>
                                                        <p className="text-muted-foreground text-sm">{row.email}</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    {row.primary_role ? (
                                                        <Badge variant="outline">{row.primary_role}</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground text-sm">No role assigned</span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <Badge variant="outline">{row.access_type || 'General'}</Badge>
                                                        <p className="text-muted-foreground text-xs">{row.access_label || 'No scoped access'}</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={row.status === 'A' ? 'default' : 'outline'}>
                                                        {row.status === 'A' ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        {canEdit && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => onEdit(row)}
                                                                title={`Edit ${row.name}`}
                                                            >
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                        )}

                                                        {canDelete && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => handleDelete(row)}
                                                                disabled={isCurrentUser}
                                                                title={
                                                                    isCurrentUser
                                                                        ? 'You cannot delete your own signed-in account'
                                                                        : `Delete ${row.name}`
                                                                }
                                                            >
                                                                <Trash2 className="text-destructive size-4" />
                                                            </Button>
                                                        )}

                                                        {canAssign && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => handleRoles(row.id)}
                                                                title={`Manage roles for ${row.name}`}
                                                            >
                                                                <ArrowRight className="size-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <div className="space-y-1">
                                                <p className="font-medium">No users found</p>
                                                <p className="text-muted-foreground text-sm">Try a different search term or create a new account.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} user{totalRows === 1 ? '' : 's'}
                            </p>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={page <= 1}
                                    onClick={() => setPage((current) => Math.max(current - 1, 1))}
                                >
                                    Previous
                                </Button>

                                <span className="text-muted-foreground text-sm">
                                    Page {page} of {totalPages}
                                </span>

                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={page >= totalPages}
                                    onClick={() => setPage((current) => Math.min(current + 1, totalPages))}
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </CardContent>
        </Card>
    );
};

export default UserList;
