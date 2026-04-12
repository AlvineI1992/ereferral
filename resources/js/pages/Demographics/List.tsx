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
import { DEMOGRAPHIC_LEVELS, type DemographicLevel, type DemographicOption, type DemographicRecord, type DemographicScope } from './types';

type Props = {
    level: DemographicLevel;
    refreshKey: number;
    scope: DemographicScope;
    onScopeChange: (scope: Partial<DemographicScope>) => void;
    onEdit: (record: DemographicRecord) => void;
};

const PAGE_SIZE_OPTIONS = [10, 20, 50] as const;

export default function DemographicList({ level, refreshKey, scope, onScopeChange, onEdit }: Props) {
    const activeLevel = useMemo(() => DEMOGRAPHIC_LEVELS.find((item) => item.value === level) ?? DEMOGRAPHIC_LEVELS[0], [level]);
    const [rows, setRows] = useState<DemographicRecord[]>([]);
    const [regionOptions, setRegionOptions] = useState<DemographicOption[]>([]);
    const [provinceOptions, setProvinceOptions] = useState<DemographicOption[]>([]);
    const [cityOptions, setCityOptions] = useState<DemographicOption[]>([]);
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
                const response = await axios.get('/demographics/list', {
                    params: {
                        level,
                        page: pageNumber,
                        perPage,
                        search,
                        status: statusFilter,
                        region_code: scope.regionCode || undefined,
                        province_code: scope.provinceCode || undefined,
                        city_code: scope.cityCode || undefined,
                    },
                });

                setRows(response.data.data ?? []);
                setTotalRows(response.data.total ?? 0);
            } catch (error) {
                console.error('Unable to load demographics:', error);
                toast.error('Unable to load demographics right now.');
            } finally {
                setLoading(false);
            }
        },
        [level, perPage, scope.cityCode, scope.provinceCode, scope.regionCode, statusFilter],
    );

    useEffect(() => {
        setPage(1);
    }, [deferredSearchTerm, statusFilter, perPage, level, scope.regionCode, scope.provinceCode, scope.cityCode]);

    useEffect(() => {
        void fetchData(page, deferredSearchTerm.trim());
    }, [deferredSearchTerm, fetchData, page, refreshKey]);

    useEffect(() => {
        const loadRegions = async () => {
            try {
                const response = await axios.get('/demographics/options/region');
                setRegionOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load regions:', error);
            }
        };

        void loadRegions();
    }, []);

    useEffect(() => {
        if (level !== 'city' && level !== 'barangay') {
            setProvinceOptions([]);
            setCityOptions([]);
            return;
        }

        if (scope.regionCode === '') {
            setProvinceOptions([]);
            setCityOptions([]);
            return;
        }

        const loadProvinces = async () => {
            try {
                const response = await axios.get('/demographics/options/province', {
                    params: {
                        region_code: scope.regionCode,
                    },
                });
                setProvinceOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load provinces:', error);
            }
        };

        void loadProvinces();
    }, [level, scope.regionCode]);

    useEffect(() => {
        if (level !== 'barangay' || scope.provinceCode === '') {
            setCityOptions([]);
            return;
        }

        const loadCities = async () => {
            try {
                const response = await axios.get('/demographics/options/city', {
                    params: {
                        province_code: scope.provinceCode,
                    },
                });
                setCityOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load cities:', error);
            }
        };

        void loadCities();
    }, [level, scope.provinceCode]);

    const handleDelete = async (row: DemographicRecord) => {
        const result = await Swal.fire({
            title: `Delete ${row.name}?`,
            text: `This ${activeLevel.shortTitle.toLowerCase()} reference will be removed.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: `Delete ${activeLevel.shortTitle.toLowerCase()}`,
        });

        if (!result.isConfirmed) {
            return;
        }

        try {
            await axios.delete(`/demographics/delete/${row.level}/${row.code}`);
            toast.success(`${row.name} deleted.`);

            if (rows.length === 1 && page > 1) {
                setPage((current) => current - 1);
                return;
            }

            await fetchData(page, deferredSearchTerm.trim());
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const message = error.response.data.errors?.form?.[0] ?? error.response.data.message ?? 'Delete failed.';
                toast.error(message);
                return;
            }

            console.error('Unable to delete demographic record:', error);
            toast.error('Unable to delete this record right now.');
        }
    };

    const totalPages = Math.max(1, Math.ceil(totalRows / perPage));
    const recordStart = totalRows === 0 ? 0 : (page - 1) * perPage + 1;
    const recordEnd = Math.min(page * perPage, totalRows);
    const pageCounts = useMemo(
        () => ({
            active: rows.filter((row) => row.status === 'A').length,
            inactive: rows.filter((row) => row.status !== 'A').length,
        }),
        [rows],
    );

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="gap-4 border-b bg-muted/20">
                <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div className="space-y-1">
                        <CardTitle className="text-base">{activeLevel.title} List</CardTitle>
                        <CardDescription>Search, filter, and maintain records for the selected demographic level.</CardDescription>
                    </div>

                    <div className="grid w-full gap-3 md:grid-cols-2 xl:w-auto xl:grid-cols-[minmax(240px,280px)_130px_130px]">
                        <div className="relative">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                type="search"
                                placeholder={`Search ${activeLevel.shortTitle.toLowerCase()} code or name`}
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

                {(level === 'province' || level === 'city' || level === 'barangay') && (
                    <div className={`grid gap-3 ${level === 'barangay' ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                        <select
                            value={scope.regionCode}
                            onChange={(event) => onScopeChange({ regionCode: event.target.value })}
                            className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <option value="">All regions</option>
                            {regionOptions.map((option) => (
                                <option key={option.code} value={option.code}>
                                    {option.name}
                                </option>
                            ))}
                        </select>

                        {(level === 'city' || level === 'barangay') && (
                            <select
                                value={scope.provinceCode}
                                onChange={(event) => onScopeChange({ provinceCode: event.target.value })}
                                disabled={!scope.regionCode}
                                className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">All provinces</option>
                                {provinceOptions.map((option) => (
                                    <option key={option.code} value={option.code}>
                                        {option.name}
                                    </option>
                                ))}
                            </select>
                        )}

                        {level === 'barangay' && (
                            <select
                                value={scope.cityCode}
                                onChange={(event) => onScopeChange({ cityCode: event.target.value })}
                                disabled={!scope.provinceCode}
                                className="border-input bg-background ring-offset-background h-9 rounded-md border px-3 py-1 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">All cities / municipalities</option>
                                {cityOptions.map((option) => (
                                    <option key={option.code} value={option.code}>
                                        {option.name}
                                    </option>
                                ))}
                            </select>
                        )}
                    </div>
                )}

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
                        Loading demographics...
                    </div>
                ) : (
                    <>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-36 whitespace-nowrap">Code</TableHead>
                                        <TableHead className="whitespace-nowrap">Name</TableHead>
                                        {level !== 'region' && <TableHead className="whitespace-nowrap">Region</TableHead>}
                                        {(level === 'city' || level === 'barangay') && <TableHead className="whitespace-nowrap">Province</TableHead>}
                                        {level === 'barangay' && <TableHead className="whitespace-nowrap">City</TableHead>}
                                        <TableHead className="w-28 whitespace-nowrap">Status</TableHead>
                                        <TableHead className="w-44 whitespace-nowrap">Updated</TableHead>
                                        <TableHead className="w-32 text-right whitespace-nowrap">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    {rows.length > 0 ? (
                                        rows.map((row) => (
                                            <TableRow key={`${row.level}-${row.code}`}>
                                                <TableCell className="font-medium whitespace-nowrap">{row.code}</TableCell>
                                                <TableCell className="max-w-[20rem] truncate whitespace-nowrap">{row.name}</TableCell>
                                                {level !== 'region' && (
                                                    <TableCell className="max-w-[14rem] truncate whitespace-nowrap">{row.parent_region_name || 'N/A'}</TableCell>
                                                )}
                                                {(level === 'city' || level === 'barangay') && (
                                                    <TableCell className="max-w-[14rem] truncate whitespace-nowrap">{row.parent_province_name || 'N/A'}</TableCell>
                                                )}
                                                {level === 'barangay' && (
                                                    <TableCell className="max-w-[14rem] truncate whitespace-nowrap">{row.parent_city_name || 'N/A'}</TableCell>
                                                )}
                                                <TableCell className="whitespace-nowrap">
                                                    <Badge variant={row.status === 'A' ? 'default' : 'outline'}>{row.status === 'A' ? 'Active' : 'Inactive'}</Badge>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {row.updated_at ? new Date(row.updated_at).toLocaleDateString() : 'N/A'}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="flex justify-end gap-2">
                                                        <Button type="button" variant="outline" size="icon" onClick={() => onEdit(row)} title={`Edit ${row.name}`}>
                                                            <Pencil className="size-4" />
                                                        </Button>
                                                        <Button type="button" variant="outline" size="icon" onClick={() => handleDelete(row)} title={`Delete ${row.name}`}>
                                                            <Trash2 className="text-destructive size-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={8} className="py-12 text-center">
                                                <div className="space-y-1">
                                                    <p className="font-medium">No {activeLevel.title.toLowerCase()} found</p>
                                                    <p className="text-muted-foreground text-sm">Adjust the search or filters, or create a new reference.</p>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground text-sm">
                                Showing {recordStart}-{recordEnd} of {totalRows} records
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
