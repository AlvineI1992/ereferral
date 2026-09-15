import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { ChevronDown, ChevronLeft, ChevronRight, FilterX, Save, Search, XCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';

type Permission = { id: number; name: string; guard_name: string; module: string; action: string; endpoint?: string | null };
type FilterOptions = { modules: string[]; actions: string[]; guards: string[] };
type Filters = { module: string; action: string; guard: string };
type ListProps = { refreshKey: unknown; id: number | null; is_include: boolean | null; onSave: () => void };
const emptyFilters: Filters = { module: '', action: '', guard: '' };

export default function RolesListAssign({ onSave, refreshKey, id: roleId, is_include: isAssign }: ListProps) {
    const [data, setData] = useState<Permission[]>([]);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [search, setSearch] = useState('');
    const [filters, setFilters] = useState<Filters>(emptyFilters);
    const [options, setOptions] = useState<FilterOptions>({ modules: [], actions: [], guards: [] });
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    const [totalRows, setTotalRows] = useState(0);
    const [totalPages, setTotalPages] = useState(1);

    useEffect(() => {
        if (!roleId) return;
        const controller = new AbortController();
        const timer = window.setTimeout(
            async () => {
                setLoading(true);
                try {
                    const response = await axios.get('/permission-has-role', {
                        params: { page, per_page: perPage, search: search || undefined, role_id: roleId, is_include: !!isAssign, ...filters },
                        signal: controller.signal,
                    });
                    setData(response.data.data ?? []);
                    setTotalRows(response.data.total ?? 0);
                    setTotalPages(Math.max(response.data.last_page ?? 1, 1));
                    setOptions(response.data.filter_options ?? { modules: [], actions: [], guards: [] });
                    setSelectedIds([]);
                } catch (error) {
                    if (!axios.isCancel(error)) void Swal.fire('Error', 'Failed to load permissions.', 'error');
                } finally {
                    if (!controller.signal.aborted) setLoading(false);
                }
            },
            search ? 300 : 0,
        );
        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [refreshKey, page, perPage, search, filters, roleId, isAssign]);

    const pageIds = useMemo(() => data.map((permission) => permission.id), [data]);
    const allPageSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));
    const activeFilterCount = Object.values(filters).filter(Boolean).length;
    const updateFilter = (key: keyof Filters, value: string) => {
        setPage(1);
        setFilters((current) => ({ ...current, [key]: value }));
    };

    const submit = async () => {
        if (!roleId || selectedIds.length === 0) {
            await Swal.fire('No selection', 'Select at least one permission.', 'warning');
            return;
        }
        setProcessing(true);
        try {
            const url = isAssign ? `/assign-permissions/${roleId}` : `/revoke-permissions/${roleId}`;
            const payload = isAssign ? { permissionids: selectedIds } : { permissionsids: selectedIds };
            const response = await axios.patch(url, payload);
            await Swal.fire('Success', response.data.message, 'success');
            setSelectedIds([]);
            onSave();
        } catch (error) {
            const message = axios.isAxiosError(error) ? error.response?.data?.message : null;
            await Swal.fire('Error', message ?? 'Unable to update role permissions.', 'error');
        } finally {
            setProcessing(false);
        }
    };

    const startEntry = totalRows ? (page - 1) * perPage + 1 : 0;
    const endEntry = Math.min(page * perPage, totalRows);

    return (
        <div className="m-3 rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b p-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 className={`flex items-center gap-2 text-lg font-semibold ${isAssign ? 'text-emerald-700' : 'text-rose-700'}`}>
                        {isAssign ? <Save className="size-4" /> : <XCircle className="size-4" />}
                        {isAssign ? 'Available permissions' : 'Assigned permissions'}
                    </h2>
                    <p className="text-xs text-slate-500">
                        {selectedIds.length} selected · {totalRows} matching permissions
                    </p>
                </div>
                <Button
                    onClick={() => void submit()}
                    disabled={processing || selectedIds.length === 0}
                    variant={isAssign ? 'default' : 'destructive'}
                >
                    {processing ? 'Processing...' : isAssign ? 'Assign selected' : 'Revoke selected'}
                </Button>
            </div>

            <div className="grid gap-2 border-b bg-slate-50/70 p-3 sm:grid-cols-2 xl:grid-cols-5">
                <div className="relative">
                    <Search className="absolute top-2.5 left-2.5 size-4 text-slate-400" />
                    <Input
                        value={search}
                        onChange={(event) => {
                            setPage(1);
                            setSearch(event.target.value);
                        }}
                        placeholder="Search permissions"
                        className="pl-8"
                    />
                </div>
                <FilterSelect
                    value={filters.module}
                    onChange={(value) => updateFilter('module', value)}
                    label="All modules"
                    options={options.modules}
                />
                <FilterSelect
                    value={filters.action}
                    onChange={(value) => updateFilter('action', value)}
                    label="All actions"
                    options={options.actions}
                />
                <FilterSelect value={filters.guard} onChange={(value) => updateFilter('guard', value)} label="All guards" options={options.guards} />
                <Button
                    type="button"
                    variant="outline"
                    disabled={!search && activeFilterCount === 0}
                    onClick={() => {
                        setSearch('');
                        setFilters(emptyFilters);
                        setPage(1);
                    }}
                >
                    <FilterX className="size-4" /> Clear filters
                </Button>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[48rem] text-left text-sm">
                    <thead className="border-b bg-white text-xs text-slate-500 uppercase">
                        <tr>
                            <th className="w-12 px-3 py-2">
                                <Checkbox
                                    checked={allPageSelected}
                                    onCheckedChange={(checked) => setSelectedIds(checked ? pageIds : [])}
                                    aria-label="Select page"
                                />
                            </th>
                            <th className="px-3 py-2">Permission</th>
                            <th className="px-3 py-2">Module</th>
                            <th className="px-3 py-2">Action</th>
                            <th className="px-3 py-2">Guard</th>
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {loading ? (
                            <tr>
                                <td colSpan={5} className="px-3 py-10 text-center text-slate-500">
                                    Loading permissions...
                                </td>
                            </tr>
                        ) : data.length ? (
                            data.map((row) => (
                                <tr key={row.id} className="hover:bg-slate-50">
                                    <td className="px-3 py-2">
                                        <Checkbox
                                            checked={selectedIds.includes(row.id)}
                                            onCheckedChange={(checked) =>
                                                setSelectedIds((current) =>
                                                    checked ? [...new Set([...current, row.id])] : current.filter((id) => id !== row.id),
                                                )
                                            }
                                            aria-label={`Select ${row.name}`}
                                        />
                                    </td>
                                    <td className="px-3 py-2 font-medium whitespace-nowrap text-slate-900">
                                        {row.name}
                                        {row.endpoint && <div className="text-muted-foreground text-xs font-normal">{row.endpoint}</div>}
                                    </td>
                                    <td className="px-3 py-2 whitespace-nowrap">{row.module}</td>
                                    <td className="px-3 py-2 capitalize">
                                        <span className="rounded-full bg-slate-100 px-2 py-1 text-xs">{row.action || 'custom'}</span>
                                    </td>
                                    <td className="px-3 py-2">{row.guard_name}</td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td colSpan={5} className="px-3 py-10 text-center text-slate-500">
                                    No permissions match the selected filters.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-col gap-2 border-t p-3 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2">
                    <span>
                        Showing {startEntry}–{endEntry} of {totalRows}
                    </span>
                    <select
                        value={perPage}
                        onChange={(event) => {
                            setPage(1);
                            setPerPage(Number(event.target.value));
                        }}
                        className="rounded border px-2 py-1"
                    >
                        <option>10</option>
                        <option>25</option>
                        <option>50</option>
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    <Button size="sm" variant="outline" disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)}>
                        <ChevronLeft className="size-4" />
                    </Button>
                    <span>
                        Page {page} of {totalPages}
                    </span>
                    <Button size="sm" variant="outline" disabled={page >= totalPages || loading} onClick={() => setPage((value) => value + 1)}>
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}

function FilterSelect({ value, onChange, label, options }: { value: string; onChange: (value: string) => void; label: string; options: string[] }) {
    return (
        <div className="relative">
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="border-input bg-background h-9 w-full appearance-none rounded-md border px-3 pr-8 text-sm"
            >
                <option value="">{label}</option>
                {options.map((option) => (
                    <option key={option} value={option}>
                        {option}
                    </option>
                ))}
            </select>
            <ChevronDown className="pointer-events-none absolute top-2.5 right-2.5 size-4 text-slate-400" />
        </div>
    );
}
