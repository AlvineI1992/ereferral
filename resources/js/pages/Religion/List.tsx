import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import axios from 'axios';
import { Pencil, Search, Trash2 } from 'lucide-react';
import { useCallback, useDeferredValue, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import Swal from 'sweetalert2';
import { type ReligionRecord } from './types';

type Props = {
    refreshKey: number;
    onEdit: (record: ReligionRecord) => void;
};

const PAGE_SIZE_OPTIONS = [10, 20, 50] as const;

export default function ReligionList({ refreshKey, onEdit }: Props) {
    const [rows, setRows] = useState<ReligionRecord[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearchTerm = useDeferredValue(searchTerm);
    const [statusFilter, setStatusFilter] = useState<'all' | 'A' | 'I'>('all');
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState<number>(PAGE_SIZE_OPTIONS[0]);
    const [totalRows, setTotalRows] = useState(0);

    const fetchData = useCallback(
        async (pageNumber: number, search: string) => {
            setLoading(true);

            try {
                const response = await axios.get('/religions/list', {
                    params: {
                        page: pageNumber,
                        perPage,
                        search,
                        status: statusFilter === 'all' ? undefined : statusFilter,
                    },
                });

                setRows(response.data.data ?? []);
                setTotalRows(response.data.total ?? 0);
            } catch (error) {
                console.error('Unable to load religions:', error);
                toast.error('Unable to load religions right now.');
            } finally {
                setLoading(false);
            }
        },
        [perPage, statusFilter],
    );

    useEffect(() => {
        setPage(1);
    }, [searchTerm, statusFilter, perPage]);

    useEffect(() => {
        void fetchData(page, deferredSearchTerm.trim());
    }, [deferredSearchTerm, fetchData, page, refreshKey]);

    const handleDelete = async (row: ReligionRecord) => {
        const result = await Swal.fire({
            title: `Delete ${row.reldesc}?`,
            text: 'This religion reference will be removed from the master list.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete religion',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/religions/delete/${row.relcode}`);
            toast.success(`${row.reldesc} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error) {
            console.error('Unable to delete religion:', error);
            toast.error('Unable to delete religion right now.');
        }
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * perPage + 1;
    const recordEnd = Math.min(page * perPage, totalRows);

    const pageCounts = useMemo(
        () => ({
            active: rows.filter((row) => row.relstat === 'A').length,
            inactive: rows.filter((row) => row.relstat === 'I').length,
        }),
        [rows],
    );

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 gap-4 border-b">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-base">Religion List</CardTitle>
                        <CardDescription>Manage religion codes used by intake, review, and API reference lookups.</CardDescription>
                    </div>

                    <div className="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(240px,300px)_130px_130px]">
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                type="search"
                                placeholder="Search code or description"
                                value={searchTerm}
                                onChange={(event) => setSearchTerm(event.target.value)}
                                className="pl-9"
                            />
                        </div>

                        <select
                            value={statusFilter}
                            onChange={(event) => setStatusFilter(event.target.value as 'all' | 'A' | 'I')}
                            className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="all">All statuses</option>
                            <option value="A">Active</option>
                            <option value="I">Inactive</option>
                        </select>

                        <select
                            value={perPage}
                            onChange={(event) => setPerPage(Number(event.target.value))}
                            className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            {PAGE_SIZE_OPTIONS.map((value) => (
                                <option key={value} value={value}>
                                    {value} / page
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="secondary">Total: {totalRows}</Badge>
                    <Badge variant="outline">Page active: {pageCounts.active}</Badge>
                    <Badge variant="outline">Page inactive: {pageCounts.inactive}</Badge>
                </div>
            </CardHeader>

            <CardContent className="space-y-4 p-0">
                {loading ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm">
                        <div className="border-primary size-5 animate-spin rounded-full border-2 border-t-transparent" />
                        Loading religions...
                    </div>
                ) : (
                    <>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-52 whitespace-nowrap">Code</TableHead>
                                    <TableHead className="whitespace-nowrap">Description</TableHead>
                                    <TableHead className="w-28 whitespace-nowrap">Status</TableHead>
                                    <TableHead className="w-44 whitespace-nowrap">Updated</TableHead>
                                    <TableHead className="w-32 text-right whitespace-nowrap">Actions</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {rows.length > 0 ? (
                                    rows.map((row) => (
                                        <TableRow key={row.relcode}>
                                            <TableCell className="font-medium whitespace-nowrap">{row.relcode}</TableCell>
                                            <TableCell className="max-w-[24rem] truncate whitespace-nowrap">{row.reldesc}</TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                <Badge variant={row.relstat === 'A' ? 'default' : 'outline'}>
                                                    {row.relstat === 'A' ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {row.updated_at ? new Date(row.updated_at).toLocaleDateString() : 'N/A'}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex justify-end gap-2">
                                                    <Button type="button" variant="outline" size="icon" onClick={() => onEdit(row)} title={`Edit ${row.reldesc}`}>
                                                        <Pencil className="size-4" />
                                                    </Button>

                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="icon"
                                                        onClick={() => handleDelete(row)}
                                                        title={`Delete ${row.reldesc}`}
                                                    >
                                                        <Trash2 className="text-destructive size-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={5} className="py-12 text-center">
                                            <div className="space-y-1">
                                                <p className="font-medium">No religions found</p>
                                                <p className="text-muted-foreground text-sm">Try a different search or create a new reference.</p>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} religions
                            </p>

                            <div className="flex items-center gap-2">
                                <Button type="button" variant="outline" disabled={page <= 1} onClick={() => setPage((current) => Math.max(current - 1, 1))}>
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
}
