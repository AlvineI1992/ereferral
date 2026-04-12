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
import { type PatientRecord } from './types';

type Props = {
    canEdit: boolean;
    canDelete: boolean;
    refreshKey: number;
    onEdit: (id: number) => void;
};

const PAGE_SIZE_OPTIONS = [10, 20, 50] as const;

export default function PatientList({ canEdit, canDelete, refreshKey, onEdit }: Props) {
    const [rows, setRows] = useState<PatientRecord[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const deferredSearchTerm = useDeferredValue(searchTerm);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState<number>(PAGE_SIZE_OPTIONS[0]);
    const [totalRows, setTotalRows] = useState(0);

    const fetchData = useCallback(
        async (pageNumber: number, search: string) => {
            setLoading(true);

            try {
                const response = await axios.get('/patient-registry/list', {
                    params: {
                        page: pageNumber,
                        perPage,
                        search,
                    },
                });

                setRows(response.data.data ?? []);
                setTotalRows(response.data.total ?? 0);
            } catch (error) {
                console.error('Unable to load patients:', error);
                toast.error('Unable to load patients right now.');
            } finally {
                setLoading(false);
            }
        },
        [perPage],
    );

    useEffect(() => {
        setPage(1);
    }, [deferredSearchTerm, perPage]);

    useEffect(() => {
        void fetchData(page, deferredSearchTerm.trim());
    }, [deferredSearchTerm, fetchData, page, refreshKey]);

    const handleDelete = async (row: PatientRecord) => {
        const result = await Swal.fire({
            title: `Delete ${row.full_name}?`,
            text: 'This patient record will be soft deleted from the registry.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Delete patient',
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/patient-registry/delete/${row.id}`);
            toast.success(`${row.full_name} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error) {
            console.error('Unable to delete patient:', error);
            toast.error('Unable to delete patient right now.');
        }
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * perPage + 1;
    const recordEnd = Math.min(page * perPage, totalRows);
    const sexCounts = useMemo(
        () => ({
            male: rows.filter((row) => row.sex === 'M').length,
            female: rows.filter((row) => row.sex === 'F').length,
        }),
        [rows],
    );

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="gap-4 border-b bg-muted/20">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-base">Patient Registry</CardTitle>
                        <CardDescription>Search patient identities, review demographics, and maintain the master list.</CardDescription>
                    </div>

                    <div className="grid w-full gap-3 sm:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(240px,300px)_130px]">
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                type="search"
                                placeholder="Search patient name, PHIC, family no."
                                value={searchTerm}
                                onChange={(event) => setSearchTerm(event.target.value)}
                                className="pl-9"
                            />
                        </div>

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
                    <Badge variant="outline">Page male: {sexCounts.male}</Badge>
                    <Badge variant="outline">Page female: {sexCounts.female}</Badge>
                </div>
            </CardHeader>

            <CardContent className="space-y-4 p-0">
                {loading ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-12 text-sm">
                        <div className="border-primary size-5 animate-spin rounded-full border-2 border-t-transparent" />
                        Loading patients...
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="whitespace-nowrap">Patient</TableHead>
                                        <TableHead className="w-24 whitespace-nowrap">Sex</TableHead>
                                        <TableHead className="w-32 whitespace-nowrap">Birth date</TableHead>
                                        <TableHead className="w-44 whitespace-nowrap">Civil status</TableHead>
                                        <TableHead className="w-44 whitespace-nowrap">Religion</TableHead>
                                        <TableHead className="whitespace-nowrap">Address</TableHead>
                                        <TableHead className="w-32 text-right whitespace-nowrap">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((row) => (
                                            <TableRow key={row.id}>
                                                <TableCell>
                                                    <div className="space-y-1">
                                                        <p className="font-medium whitespace-nowrap">{row.full_name}</p>
                                                        {row.contact_number && <p className="text-muted-foreground text-xs whitespace-nowrap">{row.contact_number}</p>}
                                                    </div>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">{row.sex_label || 'N/A'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.birth_date_label || 'N/A'}</TableCell>
                                                <TableCell className="whitespace-nowrap">{row.civil_status_label || 'N/A'}</TableCell>
                                                <TableCell className="max-w-[14rem] truncate whitespace-nowrap">{row.religion || 'N/A'}</TableCell>
                                                <TableCell className="max-w-[22rem] truncate whitespace-nowrap">{row.address || 'N/A'}</TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        {canEdit && (
                                                            <Button type="button" variant="outline" size="icon" onClick={() => onEdit(row.id)} title={`Edit ${row.full_name}`}>
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                        )}
                                                        {canDelete && (
                                                            <Button type="button" variant="outline" size="icon" onClick={() => handleDelete(row)} title={`Delete ${row.full_name}`}>
                                                                <Trash2 className="text-destructive size-4" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="py-12 text-center">
                                                <div className="space-y-1">
                                                    <p className="font-medium">No patients found</p>
                                                    <p className="text-muted-foreground text-sm">Try a different search or create a new patient record.</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} patients
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
