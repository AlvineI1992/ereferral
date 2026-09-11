import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRight, ChevronDown, FilterX, Pencil, Search, SlidersHorizontal, Trash2 } from 'lucide-react';
import { useCallback, useDeferredValue, useEffect, useState } from 'react';
import { toast } from 'sonner';
import Swal from 'sweetalert2';
import { type UserRecord } from './types';
import EmrCredentialDialog from './EmrCredentialDialog';

type Props = {
    refreshKey: number;
    onEdit: (user: UserRecord) => void;
    canDelete: boolean;
    canEdit: boolean;
    canAssign: boolean;
};

type UserFilters = {
    status: string;
    role: string;
    access_type: string;
    access_scope: string;
    created_from: string;
    created_to: string;
    sort: string;
};

type RoleOption = { id: number; name: string };

const emptyFilters: UserFilters = {
    status: '', role: '', access_type: '', access_scope: '', created_from: '', created_to: '', sort: 'name',
};

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
    const [totalPages, setTotalPages] = useState(1);
    const [perPage, setPerPage] = useState(10);
    const [filters, setFilters] = useState<UserFilters>(emptyFilters);
    const [roleOptions, setRoleOptions] = useState<RoleOption[]>([]);
    const [filtersOpen, setFiltersOpen] = useState(false);

    const currentUserId = auth.user.id;

    const fetchData = useCallback(async (pageNumber = 1, search = '', signal?: AbortSignal) => {
        setLoading(true);

        try {
            const response = await axios.get('/users/list', {
                params: {
                    page: pageNumber,
                    search,
                    per_page: perPage,
                    ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')),
                },
                signal,
            });

            setRows(response.data.data);
            setTotalRows(response.data.total);
            setTotalPages(Math.max(response.data.last_page ?? 1, 1));
            setRoleOptions(response.data.filter_options?.roles ?? []);
        } catch (error) {
            if (axios.isCancel(error)) return;
            console.error('Error fetching users:', error);
            toast.error('Unable to load user accounts right now.');
        } finally {
            if (!signal?.aborted) setLoading(false);
        }
    }, [filters, perPage]);

    useEffect(() => {
        setPage(1);
    }, [searchTerm, filters, perPage]);

    useEffect(() => {
        const controller = new AbortController();
        void fetchData(page, deferredSearchTerm.trim(), controller.signal);
        return () => controller.abort();
    }, [deferredSearchTerm, page, refreshKey, fetchData]);

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
        } catch (error) {
            console.error('Error deleting user:', error);

            const message = axios.isAxiosError(error)
                ? (error.response?.data?.message ?? 'Something went wrong while deleting this user.')
                : 'Something went wrong while deleting this user.';

            Swal.fire('Unable to delete', message, 'error');
        }
    };

    const handleRoles = (id: number) => {
        router.visit(`/users/assign-roles/${id}`);
    };

    const recordStart = totalRows === 0 ? 0 : (page - 1) * perPage + 1;
    const recordEnd = Math.min(page * perPage, totalRows);
    const activeFilterCount = Object.entries(filters).filter(([key, value]) => key !== 'sort' && value).length;

    const updateFilter = (key: keyof UserFilters, value: string) => {
        setPage(1);
        setFilters((current) => ({ ...current, [key]: value }));
    };

    const clearFilters = () => {
        setPage(1);
        setSearchTerm('');
        setFilters(emptyFilters);
    };

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 gap-4 border-b sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-1">
                    <CardTitle className="text-base">User Accounts</CardTitle>
                    <CardDescription>Review active accounts, update their access scope, or jump into role assignment.</CardDescription>
                </div>

                <div className="flex w-full gap-2 sm:w-auto">
                    <div className="relative min-w-0 flex-1 sm:w-72">
                        <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                        <Input type="search" placeholder="Search name or email" value={searchTerm} onChange={(event) => setSearchTerm(event.target.value)} className="pl-9" />
                    </div>
                    <Button type="button" variant="outline" onClick={() => setFiltersOpen((open) => !open)} aria-expanded={filtersOpen}>
                        <SlidersHorizontal className="size-4" /> Filters
                        {activeFilterCount > 0 && <Badge>{activeFilterCount}</Badge>}
                        <ChevronDown className={`size-4 transition-transform ${filtersOpen ? 'rotate-180' : ''}`} />
                    </Button>
                </div>
            </CardHeader>

            <CardContent className="space-y-4 p-0">
                {filtersOpen && (
                    <div className="grid gap-2 border-b bg-slate-50/70 p-3 sm:grid-cols-2 xl:grid-cols-4">
                        <FilterSelect label="All statuses" value={filters.status} onChange={(value) => updateFilter('status', value)} options={[['A', 'Active'], ['I', 'Inactive']]} />
                        <FilterSelect label="All roles" value={filters.role} onChange={(value) => updateFilter('role', value)} options={roleOptions.map((role) => [String(role.id), role.name])} />
                        <FilterSelect label="All access types" value={filters.access_type} onChange={(value) => updateFilter('access_type', value)} options={[['EMR', 'Provider / EMR'], ['CHD', 'Regional / CHD'], ['HOSP', 'Hospital']]} />
                        <FilterSelect label="Any access scope" value={filters.access_scope} onChange={(value) => updateFilter('access_scope', value)} options={[['scoped', 'Scoped access'], ['unscoped', 'No scoped access']]} />
                        <label className="space-y-1 text-xs text-slate-600"><span>Created from</span><Input type="date" value={filters.created_from} onChange={(event) => updateFilter('created_from', event.target.value)} /></label>
                        <label className="space-y-1 text-xs text-slate-600"><span>Created to</span><Input type="date" value={filters.created_to} onChange={(event) => updateFilter('created_to', event.target.value)} /></label>
                        <FilterSelect label="Sort by name" value={filters.sort} onChange={(value) => updateFilter('sort', value)} options={[['name', 'Name A–Z'], ['newest', 'Newest first'], ['oldest', 'Oldest first']]} />
                        <Button type="button" variant="outline" onClick={clearFilters} disabled={!searchTerm && activeFilterCount === 0 && filters.sort === 'name'}><FilterX className="size-4" /> Clear all</Button>
                    </div>
                )}
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
                                    <TableHead className="whitespace-nowrap">EMR ID / Token</TableHead>
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
                                                <TableCell className="whitespace-nowrap">
                                                    {canEdit ? (
                                                        row.access_type === 'EMR' ? <EmrCredentialDialog user={row} /> : (
                                                            <span className="text-muted-foreground text-xs">Requires EMR provider access</span>
                                                        )
                                                    ) : (
                                                        <span className="text-muted-foreground text-xs">Requires user edit permission</span>
                                                    )}
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
                                        <TableCell colSpan={6} className="py-12 text-center">
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
                            <div className="flex items-center gap-2 text-sm text-muted-foreground"><span>Showing {recordStart}-{recordEnd} of {totalRows} user{totalRows === 1 ? '' : 's'}</span><select value={perPage} onChange={(event) => { setPage(1); setPerPage(Number(event.target.value)); }} className="border-input bg-background rounded-md border px-2 py-1"><option>10</option><option>25</option><option>50</option></select></div>

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

function FilterSelect({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: string[][] }) {
    return (
        <select value={value} onChange={(event) => onChange(event.target.value)} className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm">
            <option value="">{label}</option>
            {options.map(([optionValue, optionLabel]) => <option key={optionValue} value={optionValue}>{optionLabel}</option>)}
        </select>
    );
}
