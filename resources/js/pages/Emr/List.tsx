import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRight, Pencil, Search, Trash2 } from 'lucide-react';
import { useDeferredValue, useEffect, useState } from 'react';
import { toast } from 'sonner';
import Swal from 'sweetalert2';
import { type EmrRecord } from './types';

type ListProps = {
    refreshKey: number;
    onEdit: (emr: EmrRecord) => void;
    canEdit: boolean;
    canDelete: boolean;
};

const PAGE_SIZE = 10;

const isActive = (status: EmrRecord['status']) => Number(status) === 1 || status === true || status === '1';

const Lists = ({ refreshKey, onEdit, canEdit, canDelete }: ListProps) => {
    const [rows, setRows] = useState<EmrRecord[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearchTerm = useDeferredValue(searchTerm);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalRows, setTotalRows] = useState(0);

    const fetchData = async (pageNumber = 1, search = '') => {
        setLoading(true);

        try {
            const response = await axios.get('/emr/list', {
                params: {
                    page: pageNumber,
                    search,
                },
            });

            setRows(response.data.data);
            setTotalRows(response.data.total);
        } catch (error) {
            console.error('Fetch error:', error);
            toast.error('Unable to load providers right now.');
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

    const handleDelete = async (row: EmrRecord) => {
        if ((row.assigned_facilities_count ?? 0) > 0) {
            toast.error('Unassign this provider from its facilities before deleting it.');
            return;
        }

        const result = await Swal.fire({
            title: `Delete ${row.emr_name}?`,
            text: 'This provider will be removed from the active directory.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete provider',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/emr/delete/${row.emr_id}`);
            toast.success(`${row.emr_name} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error: any) {
            console.error('Delete error:', error);

            const message = error.response?.data?.message ?? 'Something went wrong while deleting this provider.';

            Swal.fire('Unable to delete', message, 'error');
        }
    };

    const handleProfile = (id: number) => {
        router.visit(`/emr/profile/${id}`);
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / PAGE_SIZE));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * PAGE_SIZE + 1;
    const recordEnd = Math.min(page * PAGE_SIZE, totalRows);

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 gap-4 border-b sm:flex-row sm:items-end sm:justify-between">
                <div className="space-y-1">
                    <CardTitle className="text-base">Provider List</CardTitle>
                    <CardDescription>Search the directory, open a provider profile, or load a row into the editor.</CardDescription>
                </div>

                <div className="relative w-full sm:w-72">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        type="search"
                        placeholder="Search provider name, remarks, or ID"
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
                        Loading providers...
                    </div>
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-20">ID</TableHead>
                                    <TableHead>Provider</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Facilities</TableHead>
                                    <TableHead className="min-w-52">Remarks</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {rows.length > 0 ? (
                                    rows.map((row) => {
                                        const assignedCount = row.assigned_facilities_count ?? 0;

                                        return (
                                            <TableRow key={row.emr_id}>
                                                <TableCell className="font-medium">{row.emr_id}</TableCell>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p className="font-medium">{row.emr_name}</p>
                                                        <p className="text-muted-foreground text-xs">Provider record</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={isActive(row.status) ? 'default' : 'outline'}>
                                                        {isActive(row.status) ? 'Active' : 'Inactive'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-muted-foreground text-sm">
                                                        {assignedCount} {assignedCount === 1 ? 'facility' : 'facilities'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-muted-foreground max-w-xs text-sm whitespace-normal">
                                                    {row.remarks?.trim() ? row.remarks : 'No remarks'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        {canEdit && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => onEdit(row)}
                                                                title={`Edit ${row.emr_name}`}
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
                                                                disabled={assignedCount > 0}
                                                                title={
                                                                    assignedCount > 0
                                                                        ? 'Unassign facilities before deleting'
                                                                        : `Delete ${row.emr_name}`
                                                                }
                                                            >
                                                                <Trash2 className="text-destructive size-4" />
                                                            </Button>
                                                        )}

                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => handleProfile(row.emr_id)}
                                                            title={`Open ${row.emr_name} profile`}
                                                        >
                                                            <ArrowRight className="size-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-12 text-center">
                                            <div className="space-y-1">
                                                <p className="font-medium">No providers found</p>
                                                <p className="text-muted-foreground text-sm">Try a different search term or create a new provider.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} provider
                                {totalRows === 1 ? '' : 's'}
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

export default Lists;
