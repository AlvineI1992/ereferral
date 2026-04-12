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
import { type BedTrackerRecord, type FacilityOption } from './types';

type Props = {
    canEdit: boolean;
    canDelete: boolean;
    refreshKey: number;
    onEdit: (id: number) => void;
};

const PAGE_SIZE_OPTIONS = [10, 20, 50] as const;

export default function BedTrackerList({ canEdit, canDelete, refreshKey, onEdit }: Props) {
    const [rows, setRows] = useState<BedTrackerRecord[]>([]);
    const [facilities, setFacilities] = useState<FacilityOption[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearchTerm = useDeferredValue(searchTerm);
    const [statusFilter, setStatusFilter] = useState<'all' | 'A' | 'I'>('all');
    const [facilityFilter, setFacilityFilter] = useState('all');
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState<number>(PAGE_SIZE_OPTIONS[0]);
    const [totalRows, setTotalRows] = useState(0);

    const fetchData = useCallback(
        async (pageNumber: number, search: string) => {
            setLoading(true);

            try {
                const response = await axios.get('/bed-tracker/list', {
                    params: {
                        page: pageNumber,
                        perPage,
                        search,
                        status: statusFilter === 'all' ? undefined : statusFilter,
                        facility_hfhudcode: facilityFilter === 'all' ? undefined : facilityFilter,
                    },
                });

                setRows(response.data.data ?? []);
                setTotalRows(response.data.total ?? 0);
            } catch (error) {
                console.error('Unable to load bed tracker records:', error);
                toast.error('Unable to load bed tracker records right now.');
            } finally {
                setLoading(false);
            }
        },
        [facilityFilter, perPage, statusFilter],
    );

    useEffect(() => {
        const loadFacilities = async () => {
            try {
                const response = await axios.get('/bed-tracker/facilities');
                const rows = response.data.data ?? [];
                setFacilities(rows);
                if (rows.length === 1) {
                    setFacilityFilter(rows[0].hfhudcode);
                }
            } catch (error) {
                console.error('Unable to load facilities:', error);
            }
        };

        void loadFacilities();
    }, []);

    useEffect(() => {
        setPage(1);
    }, [deferredSearchTerm, facilityFilter, perPage, statusFilter]);

    useEffect(() => {
        void fetchData(page, deferredSearchTerm.trim());
    }, [deferredSearchTerm, fetchData, page, refreshKey]);

    const handleDelete = async (row: BedTrackerRecord) => {
        const result = await Swal.fire({
            title: `Delete ${row.bed_type}?`,
            text: 'This bed tracker record will be removed.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete record',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/bed-tracker/delete/${row.id}`);
            toast.success(`${row.bed_type} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error) {
            console.error('Unable to delete bed tracker record:', error);
            toast.error('Unable to delete bed tracker record right now.');
        }
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * perPage + 1;
    const recordEnd = Math.min(page * perPage, totalRows);
    const totals = useMemo(
        () => ({
            totalBeds: rows.reduce((sum, row) => sum + row.total_beds, 0),
            occupiedBeds: rows.reduce((sum, row) => sum + row.occupied_beds, 0),
            availableBeds: rows.reduce((sum, row) => sum + row.available_beds, 0),
        }),
        [rows],
    );

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="gap-4 border-b bg-muted/20">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-base">Bed Tracker List</CardTitle>
                        <CardDescription>Review available beds across the facilities visible to the current account.</CardDescription>
                    </div>

                    <div className="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(220px,280px)_170px_140px_130px]">
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                type="search"
                                placeholder="Search facility or bed type"
                                value={searchTerm}
                                onChange={(event) => setSearchTerm(event.target.value)}
                                className="pl-9"
                            />
                        </div>

                        <select
                            value={facilityFilter}
                            onChange={(event) => setFacilityFilter(event.target.value)}
                            className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="all">All facilities</option>
                            {facilities.map((facility) => (
                                <option key={facility.hfhudcode} value={facility.hfhudcode}>
                                    {facility.facility_name}
                                </option>
                            ))}
                        </select>

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
                    <Badge variant="secondary">Total rows: {totalRows}</Badge>
                    <Badge variant="outline">Page total beds: {totals.totalBeds}</Badge>
                    <Badge variant="outline">Page occupied: {totals.occupiedBeds}</Badge>
                    <Badge variant="outline">Page available: {totals.availableBeds}</Badge>
                </div>
            </CardHeader>

            <CardContent className="space-y-4 p-0">
                {loading ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm">
                        <div className="border-primary size-5 animate-spin rounded-full border-2 border-t-transparent" />
                        Loading bed tracker records...
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="whitespace-nowrap">Facility</TableHead>
                                        <TableHead className="whitespace-nowrap">Bed Type</TableHead>
                                        <TableHead className="w-24 whitespace-nowrap">Total</TableHead>
                                        <TableHead className="w-24 whitespace-nowrap">Occupied</TableHead>
                                        <TableHead className="w-24 whitespace-nowrap">Reserved</TableHead>
                                        <TableHead className="w-24 whitespace-nowrap">Available</TableHead>
                                        <TableHead className="w-28 whitespace-nowrap">Occupancy</TableHead>
                                        <TableHead className="w-28 whitespace-nowrap">Status</TableHead>
                                        <TableHead className="w-32 text-right whitespace-nowrap">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p className="font-medium whitespace-nowrap">{row.facility_name || row.facility_hfhudcode}</p>
                                                        <p className="text-muted-foreground text-xs whitespace-nowrap">{row.region_name || row.facility_hfhudcode}</p>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="font-medium whitespace-nowrap">{row.bed_type}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.total_beds}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.occupied_beds}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.reserved_beds}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.available_beds}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.occupancy_rate}%</TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    <Badge variant={row.status === 'A' ? 'default' : 'outline'}>{row.status_label}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        {canEdit && (
                                                            <Button type="button" variant="outline" size="icon" onClick={() => onEdit(row.id)} title={`Edit ${row.bed_type}`}>
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                        )}
                                                        {canDelete && (
                                                            <Button type="button" variant="outline" size="icon" onClick={() => handleDelete(row)} title={`Delete ${row.bed_type}`}>
                                                                <Trash2 className="text-destructive size-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="py-12 text-center">
                                                <div className="space-y-1">
                                                    <p className="font-medium">No bed tracker records found</p>
                                                    <p className="text-muted-foreground text-sm">Adjust the filters or create a new record.</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} bed tracker records
                            </p>
                            <div className="flex items-center gap-2">
                                <Button type="button" variant="outline" disabled={page <= 1} onClick={() => setPage((current) => Math.max(current - 1, 1))}>
                                    Previous
                                </Button>
                                <span className="text-muted-foreground text-sm">
                                    Page {page} of {totalPages}
                                </span>
                                <Button type="button" variant="outline" disabled={page >= totalPages} onClick={() => setPage((current) => Math.min(current + 1, totalPages))}>
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
