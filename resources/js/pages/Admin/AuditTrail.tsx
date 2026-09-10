import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { Search, ShieldCheck } from 'lucide-react';
import { FormEvent, useState } from 'react';

type Audit = {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: string;
    user_name: string;
    user_email: string | null;
    changed_fields: string[];
    url: string | null;
    ip_address: string | null;
    created_at: string;
};

type PaginatedAudits = {
    data: Audit[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Filters = { search?: string; event?: string; date_from?: string; date_to?: string };

export default function AuditTrail({ audits, filters }: { audits: PaginatedAudits; filters: Filters }) {
    const [form, setForm] = useState({
        search: filters.search ?? '',
        event: filters.event ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
    });

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get('/admin/audit-trail', form, { preserveState: true, replace: true });
    };

    const clearFilters = () => {
        setForm({ search: '', event: '', date_from: '', date_to: '' });
        router.get('/admin/audit-trail', {}, { replace: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Audit Trail', href: '/admin/audit-trail' }]}>
            <Head title="Audit Trail" />
            <div className="flex flex-col gap-3 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <div className="rounded-lg bg-teal-50 p-2 text-teal-700"><ShieldCheck className="size-5" /></div>
                    <div>
                        <h1 className="text-xl font-semibold text-slate-900">Audit Trail</h1>
                        <p className="text-xs text-slate-500">Review recorded system and user activity.</p>
                    </div>
                </div>

                <form onSubmit={applyFilters} className="grid gap-2 rounded-xl border bg-white p-3 shadow-sm sm:grid-cols-2 xl:grid-cols-5">
                    <Input value={form.search} onChange={(e) => setForm({ ...form, search: e.target.value })} placeholder="User, model, ID, or IP" />
                    <select value={form.event} onChange={(e) => setForm({ ...form, event: e.target.value })} className="border-input bg-background h-9 rounded-md border px-3 text-sm">
                        <option value="">All events</option>
                        <option value="created">Created</option>
                        <option value="updated">Updated</option>
                        <option value="deleted">Deleted</option>
                        <option value="restored">Restored</option>
                    </select>
                    <Input type="date" value={form.date_from} onChange={(e) => setForm({ ...form, date_from: e.target.value })} aria-label="Date from" />
                    <Input type="date" value={form.date_to} onChange={(e) => setForm({ ...form, date_to: e.target.value })} aria-label="Date to" />
                    <div className="flex gap-2">
                        <Button type="submit" size="sm" className="flex-1"><Search className="size-4" /> Filter</Button>
                        <Button type="button" size="sm" variant="outline" onClick={clearFilters}>Clear</Button>
                    </div>
                </form>

                <div className="overflow-x-auto rounded-xl border bg-white shadow-sm">
                    <table className="w-full min-w-[70rem] text-left text-sm">
                        <thead className="border-b bg-slate-50 text-xs uppercase text-slate-600">
                            <tr>
                                <th className="px-3 py-2">Date and time</th><th className="px-3 py-2">User</th><th className="px-3 py-2">Event</th>
                                <th className="px-3 py-2">Record</th><th className="px-3 py-2">Changed fields</th><th className="px-3 py-2">IP address</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {audits.data.map((audit) => (
                                <tr key={audit.id} className="hover:bg-slate-50/70">
                                    <td className="whitespace-nowrap px-3 py-2">{new Date(audit.created_at).toLocaleString()}</td>
                                    <td className="whitespace-nowrap px-3 py-2"><div className="font-medium">{audit.user_name}</div><div className="text-xs text-slate-500">{audit.user_email}</div></td>
                                    <td className="px-3 py-2 capitalize"><span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium">{audit.event}</span></td>
                                    <td className="whitespace-nowrap px-3 py-2"><span className="font-medium">{audit.auditable_type}</span> #{audit.auditable_id}</td>
                                    <td className="max-w-80 truncate px-3 py-2" title={audit.changed_fields.join(', ')}>{audit.changed_fields.join(', ') || '—'}</td>
                                    <td className="whitespace-nowrap px-3 py-2">{audit.ip_address || '—'}</td>
                                </tr>
                            ))}
                            {audits.data.length === 0 && <tr><td colSpan={6} className="px-3 py-10 text-center text-slate-500">No audit records found.</td></tr>}
                        </tbody>
                    </table>
                    <div className="flex items-center justify-between border-t px-3 py-2 text-xs text-slate-600">
                        <span>{audits.total.toLocaleString()} records · Page {audits.current_page} of {audits.last_page}</span>
                        <div className="flex gap-2">
                            <Button size="sm" variant="outline" disabled={!audits.prev_page_url} onClick={() => audits.prev_page_url && router.visit(audits.prev_page_url)}>Previous</Button>
                            <Button size="sm" variant="outline" disabled={!audits.next_page_url} onClick={() => audits.next_page_url && router.visit(audits.next_page_url)}>Next</Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
