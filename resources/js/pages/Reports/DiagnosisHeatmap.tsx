import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import DiagnosisMap, { type HeatFacility } from './DiagnosisMap';

type Filters = { date_from: string; date_to: string; diagnosis: string };
type Report = { facilities: HeatFacility[]; diagnoses: { diagnosis: string; count: number }[]; total: number; mapped: number; unmapped: number };
const empty: Report = { facilities: [], diagnoses: [], total: 0, mapped: 0, unmapped: 0 };

export default function DiagnosisHeatmap({ defaults }: { defaults: { date_from: string; date_to: string } }) {
    const [filters, setFilters] = useState<Filters>({ ...defaults, diagnosis: '' });
    const [applied, setApplied] = useState(filters);
    const [report, setReport] = useState<Report>(empty);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    useEffect(() => {
        const controller = new AbortController();
        setLoading(true); setError(''); setReport(empty);
        axios.get<Report>('/reports/diagnosis-heatmap/data', { params: applied, signal: controller.signal })
            .then(({ data }) => { if (!controller.signal.aborted) setReport(data); })
            .catch((reason) => { if (!axios.isCancel(reason)) setError(reason.response?.data?.message || 'Unable to load the diagnosis report.'); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [applied]);
    return <AppLayout breadcrumbs={[{ title: 'Diagnosis Heat Map', href: '/reports/diagnosis-heatmap' }]}>
        <Head title="Diagnosis Heat Map" />
        <div className="space-y-4 p-4">
            <div><h1 className="text-xl font-semibold">Philippines Diagnosis Heat Map</h1><p className="text-muted-foreground text-sm">Final diagnoses recorded at discharge, grouped by receiving facility within your access scope.</p></div>
            <form className="grid items-end gap-3 rounded-lg border p-3 sm:grid-cols-2 lg:grid-cols-4" onSubmit={(event) => { event.preventDefault(); setApplied({ ...filters }); }}>
                <div className="space-y-1"><Label htmlFor="date_from">Discharged from</Label><Input id="date_from" type="date" required value={filters.date_from} max={filters.date_to} onChange={(event) => setFilters({ ...filters, date_from: event.target.value })} /></div>
                <div className="space-y-1"><Label htmlFor="date_to">Discharged to</Label><Input id="date_to" type="date" required value={filters.date_to} min={filters.date_from} max={defaults.date_to} onChange={(event) => setFilters({ ...filters, date_to: event.target.value })} /></div>
                <div className="space-y-1"><Label htmlFor="diagnosis">Final diagnosis</Label><Input id="diagnosis" list="diagnosis-options" placeholder="All final diagnoses" maxLength={2000} value={filters.diagnosis} onChange={(event) => setFilters({ ...filters, diagnosis: event.target.value })} /><datalist id="diagnosis-options">{report.diagnoses.map((item) => <option key={item.diagnosis} value={item.diagnosis}>{item.count} referrals</option>)}</datalist></div>
                <Button type="submit" disabled={loading}>{loading ? 'Loading...' : 'Apply filters'}</Button>
            </form>
            <p className="text-muted-foreground text-xs">Leave diagnosis blank for all records, or select an exact recorded diagnosis. Suggestions show the top 100. Each discharged referral is counted once; combined diagnosis text is retained as recorded.</p>
            {error && <p role="alert" className="text-destructive text-sm">{error}</p>}
            <div className="grid grid-cols-3 gap-3">{[['Final diagnosis records', report.total], ['Mapped records', report.mapped], ['Unmapped records', report.unmapped]].map(([label, count]) => <div key={label} className="rounded-lg border p-3"><p className="text-muted-foreground text-xs">{label}</p><p className="text-2xl font-semibold">{loading ? '…' : Number(count).toLocaleString()}</p></div>)}</div>
            <DiagnosisMap facilities={report.facilities} />
            {!loading && !error && report.total === 0 && <p className="text-muted-foreground text-sm">No discharged referrals with a final diagnosis match these filters.</p>}
            {report.unmapped > 0 && <p className="text-sm">{report.unmapped.toLocaleString()} records cannot be plotted because their facility coordinates are missing or outside the Philippines map bounds. Update the facility’s map location to include them.</p>}
            <div className="max-h-96 overflow-auto rounded-lg border"><Table><TableHeader><TableRow><TableHead>Receiving facility</TableHead><TableHead>Final diagnosis records</TableHead><TableHead>Map status</TableHead></TableRow></TableHeader><TableBody>{report.facilities.map((facility) => <TableRow key={facility.code}><TableCell className="whitespace-nowrap">{facility.name}</TableCell><TableCell>{facility.count.toLocaleString()}</TableCell><TableCell className="whitespace-nowrap">{facility.mapped ? 'Mapped' : 'Coordinates needed'}</TableCell></TableRow>)}</TableBody></Table></div>
        </div>
    </AppLayout>;
}
