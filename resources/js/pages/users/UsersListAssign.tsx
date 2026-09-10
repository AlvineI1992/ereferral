import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { ChevronLeft, ChevronRight, FilterX, KeyRound, Save, Search, ShieldCheck, XCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import Swal from 'sweetalert2';

type Role = { id: number; name: string; guard_name: string };
type ListProps = { refreshKey: number; id: number | null; is_include: boolean | null; onSave: () => void };
type Filters = { guard: string; sort: string };
const emptyFilters: Filters = { guard: '', sort: 'name' };

export default function UsersListAssign({ onSave, refreshKey, id: userId, is_include: isAssign }: ListProps) {
    const [data, setData] = useState<Role[]>([]);
    const [selectedIds, setSelectedIds] = useState<number[]>([]);
    const [search, setSearch] = useState('');
    const [filters, setFilters] = useState<Filters>(emptyFilters);
    const [guardOptions, setGuardOptions] = useState<string[]>([]);
    const [loading, setLoading] = useState(true);
    const [processing, setProcessing] = useState(false);
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(10);
    const [totalRows, setTotalRows] = useState(0);
    const [totalPages, setTotalPages] = useState(1);

    useEffect(() => {
        if (!userId) return;
        const controller = new AbortController();
        const timer = window.setTimeout(async () => {
            setLoading(true);
            try {
                const response = await axios.get('/user-has-role', {
                    params: { page, per_page: perPage, search: search || undefined, user_id: userId, is_include: !!isAssign, ...filters },
                    signal: controller.signal,
                });
                setData(response.data.data ?? []);
                setTotalRows(response.data.total ?? 0);
                setTotalPages(Math.max(response.data.last_page ?? 1, 1));
                setGuardOptions(response.data.filter_options?.guards ?? []);
                setSelectedIds([]);
            } catch (error) {
                if (!axios.isCancel(error)) await Swal.fire('Error', 'Failed to load roles.', 'error');
            } finally {
                if (!controller.signal.aborted) setLoading(false);
            }
        }, search ? 300 : 0);

        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [refreshKey, page, perPage, search, filters, userId, isAssign]);

    const pageIds = useMemo(() => data.map((role) => role.id), [data]);
    const allPageSelected = pageIds.length > 0 && pageIds.every((id) => selectedIds.includes(id));

    const submit = async () => {
        if (!userId || selectedIds.length === 0) {
            await Swal.fire('No selection', 'Select at least one role.', 'warning');
            return;
        }

        setProcessing(true);
        try {
            const url = isAssign ? `/users/assign-roles/${userId}` : `/users/revoke-roles/${userId}`;
            const response = await axios.patch(url, { roleids: selectedIds });
            await Swal.fire('Success', response.data.message, 'success');
            setSelectedIds([]);
            onSave();
        } catch (error) {
            const message = axios.isAxiosError(error) ? error.response?.data?.message : null;
            await Swal.fire('Error', message ?? 'Unable to update user roles.', 'error');
        } finally {
            setProcessing(false);
        }
    };

    const updateFilter = (key: keyof Filters, value: string) => { setPage(1); setFilters((current) => ({ ...current, [key]: value })); };
    const startEntry = totalRows ? (page - 1) * perPage + 1 : 0;
    const endEntry = Math.min(page * perPage, totalRows);

    return (
        <div className="m-3 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className={`flex items-center gap-2 text-lg font-semibold ${isAssign ? 'text-emerald-700' : 'text-rose-700'}`}>
                        {isAssign ? <ShieldCheck className="size-5" /> : <KeyRound className="size-5" />}
                        {isAssign ? 'Available roles' : 'Assigned roles'}
                    </h2>
                    <p className="mt-0.5 text-xs text-slate-500">{selectedIds.length} selected · {totalRows} matching roles</p>
                </div>
                <Button variant={isAssign ? 'default' : 'destructive'} disabled={processing || selectedIds.length === 0} onClick={() => void submit()}>
                    {processing ? 'Processing...' : isAssign ? <><Save className="size-4" /> Assign selected</> : <><XCircle className="size-4" /> Revoke selected</>}
                </Button>
            </div>

            <div className="grid gap-2 border-b bg-slate-50/70 p-3 sm:grid-cols-2 lg:grid-cols-4">
                <div className="relative"><Search className="absolute top-2.5 left-2.5 size-4 text-slate-400" /><Input value={search} onChange={(event) => { setPage(1); setSearch(event.target.value); }} placeholder="Search role" className="pl-8" /></div>
                <select value={filters.guard} onChange={(event) => updateFilter('guard', event.target.value)} className="border-input bg-background h-9 rounded-md border px-3 text-sm"><option value="">All guards</option>{guardOptions.map((guard) => <option key={guard}>{guard}</option>)}</select>
                <select value={filters.sort} onChange={(event) => updateFilter('sort', event.target.value)} className="border-input bg-background h-9 rounded-md border px-3 text-sm"><option value="name">Name A–Z</option><option value="newest">Newest first</option><option value="oldest">Oldest first</option></select>
                <Button variant="outline" disabled={!search && !filters.guard && filters.sort === 'name'} onClick={() => { setSearch(''); setFilters(emptyFilters); setPage(1); }}><FilterX className="size-4" /> Clear filters</Button>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[36rem] text-left text-sm">
                    <thead className="border-b text-xs uppercase text-slate-500"><tr><th className="w-12 px-3 py-2"><Checkbox checked={allPageSelected} onCheckedChange={(checked) => setSelectedIds(checked ? pageIds : [])} aria-label="Select page" /></th><th className="px-3 py-2">Role</th><th className="px-3 py-2">Guard</th></tr></thead>
                    <tbody className="divide-y">
                        {loading ? <tr><td colSpan={3} className="py-10 text-center text-slate-500">Loading roles...</td></tr> : data.length ? data.map((role) => (
                            <tr key={role.id} className="hover:bg-slate-50"><td className="px-3 py-2"><Checkbox checked={selectedIds.includes(role.id)} onCheckedChange={(checked) => setSelectedIds((current) => checked ? [...new Set([...current, role.id])] : current.filter((id) => id !== role.id))} aria-label={`Select ${role.name}`} /></td><td className="px-3 py-2 font-medium">{role.name}</td><td className="px-3 py-2"><span className="rounded-full bg-slate-100 px-2 py-1 text-xs">{role.guard_name}</span></td></tr>
                        )) : <tr><td colSpan={3} className="py-10 text-center text-slate-500">No roles match the selected filters.</td></tr>}
                    </tbody>
                </table>
            </div>

            <div className="flex flex-col gap-2 border-t p-3 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex items-center gap-2"><span>Showing {startEntry}–{endEntry} of {totalRows}</span><select value={perPage} onChange={(event) => { setPage(1); setPerPage(Number(event.target.value)); }} className="rounded border px-2 py-1"><option>5</option><option>10</option><option>25</option></select></div>
                <div className="flex items-center gap-2"><Button size="sm" variant="outline" disabled={page <= 1 || loading} onClick={() => setPage((value) => value - 1)}><ChevronLeft className="size-4" /></Button><span>Page {page} of {totalPages}</span><Button size="sm" variant="outline" disabled={page >= totalPages || loading} onClick={() => setPage((value) => value + 1)}><ChevronRight className="size-4" /></Button></div>
            </div>
        </div>
    );
}
