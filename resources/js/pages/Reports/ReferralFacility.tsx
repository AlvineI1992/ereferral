import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { Download, Eye, RefreshCw, Trash2 } from 'lucide-react';
import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

type ReportRow = {
    facility_code: string;
    facility_name: string;
    facility_type: string;
    sent_count: number;
    rhu_sent_count: number;
    received_count: number;
    pending_count: number;
    receipt_rate: number;
};

type DailyTrend = {
    date: string;
    label: string;
    sent_count: number;
    received_count: number;
};

type Summary = {
    facilities: number;
    rhu_facilities: number;
    sent_count: number;
    rhu_sent_count: number;
    received_count: number;
    pending_count: number;
    receipt_rate: number;
    average_daily_referrals: number;
    busiest_day: string | null;
    busiest_day_count: number;
};

type RhuReportRow = {
    rhu_code: string;
    rhu_name: string;
    destination_count: number;
    sent_count: number;
    received_count: number;
    pending_count: number;
    receipt_rate: number;
};

type ReportResponse = {
    filters: { date_from: string; date_to: string; provider: string | null; referring_facility: string | null; referral_facility: string | null };
    summary: Summary;
    data: ReportRow[];
    rhu_data: RhuReportRow[];
    trend: DailyTrend[];
    options: {
        providers: ProviderOption[];
        referring_facilities: FacilityOption[];
        referral_facilities: FacilityOption[];
    };
    generated_at: string;
};

type FacilityOption = { code: string; name: string };
type ProviderOption = { id: string; name: string };

type PatientReferral = {
    log_id: string;
    patient_name: string;
    referring_facility: string;
    referral_facility: string;
    referral_date: string;
    received: boolean;
    received_date: string | null;
};

const incomingProfileUrl = (logId: string) => `/incoming/profile/${btoa(logId)}`;

const emptySummary: Summary = {
    facilities: 0,
    rhu_facilities: 0,
    sent_count: 0,
    rhu_sent_count: 0,
    received_count: 0,
    pending_count: 0,
    receipt_rate: 0,
    average_daily_referrals: 0,
    busiest_day: null,
    busiest_day_count: 0,
};

const breadcrumbs = [{ title: 'Referral Report', href: '/reports/referrals-by-facility' }];

function DailyReferralTrend({ data }: { data: DailyTrend[] }) {
    const width = 720;
    const height = 220;
    const padding = 28;
    const maxValue = Math.max(...data.flatMap((day) => [day.sent_count, day.received_count]), 1);
    const x = (index: number) => padding + (index * (width - padding * 2)) / Math.max(data.length - 1, 1);
    const y = (value: number) => height - padding - (value / maxValue) * (height - padding * 2);
    const sentPoints = data.map((day, index) => `${x(index)},${y(day.sent_count)}`).join(' ');
    const receivedPoints = data.map((day, index) => `${x(index)},${y(day.received_count)}`).join(' ');
    const labelStep = Math.max(Math.ceil(data.length / 7), 1);

    return (
        <div className="overflow-x-auto">
            <svg className="h-64 w-full min-w-[45rem]" viewBox={`0 0 ${width} ${height}`} role="img" aria-label="Daily sent and received referrals">
                {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
                    const lineY = padding + ratio * (height - padding * 2);
                    return <line key={ratio} x1={padding} y1={lineY} x2={width - padding} y2={lineY} className="stroke-border" />;
                })}
                <polyline points={sentPoints} fill="none" className="stroke-blue-600" strokeWidth="3" strokeLinejoin="round" strokeLinecap="round" />
                <polyline
                    points={receivedPoints}
                    fill="none"
                    className="stroke-emerald-600"
                    strokeWidth="3"
                    strokeLinejoin="round"
                    strokeLinecap="round"
                />
                {data.map((day, index) => (
                    <g key={day.date}>
                        <circle cx={x(index)} cy={y(day.sent_count)} r="3" className="fill-blue-600">
                            <title>{`${day.label}: ${day.sent_count} sent`}</title>
                        </circle>
                        <circle cx={x(index)} cy={y(day.received_count)} r="3" className="fill-emerald-600">
                            <title>{`${day.label}: ${day.received_count} received`}</title>
                        </circle>
                        {(index % labelStep === 0 || index === data.length - 1) && (
                            <text x={x(index)} y={height - 6} textAnchor="middle" className="fill-muted-foreground text-[10px]">
                                {day.label}
                            </text>
                        )}
                    </g>
                ))}
            </svg>
        </div>
    );
}

function FacilityVolumeChart({ data }: { data: ReportRow[] }) {
    const rows = data.slice(0, 8);
    const maxValue = Math.max(...rows.map((row) => row.sent_count), 1);

    return (
        <div className="space-y-3">
            {rows.length ? (
                rows.map((row) => (
                    <div key={row.facility_code} className="space-y-1">
                        <div className="flex items-center justify-between gap-3 text-xs">
                            <span className="truncate font-medium" title={row.facility_name}>
                                {row.facility_name}
                            </span>
                            <span className="shrink-0 tabular-nums">{row.sent_count}</span>
                        </div>
                        <svg
                            className="h-3 w-full rounded-full bg-slate-100"
                            viewBox="0 0 100 12"
                            preserveAspectRatio="none"
                            role="img"
                            aria-label={`${row.facility_name}: ${row.sent_count} referrals`}
                        >
                            <rect width={(row.sent_count / maxValue) * 100} height="12" rx="6" className="fill-blue-600" />
                        </svg>
                    </div>
                ))
            ) : (
                <p className="text-muted-foreground py-16 text-center text-sm">No facility volume for this period.</p>
            )}
        </div>
    );
}

export default function ReferralFacilityReport({ canDelete = false }: { canDelete?: boolean }) {
    const today = new Date().toISOString().slice(0, 10);
    const monthStart = `${today.slice(0, 8)}01`;
    const [dateFrom, setDateFrom] = useState(monthStart);
    const [dateTo, setDateTo] = useState(today);
    const [provider, setProvider] = useState('');
    const [referringFacility, setReferringFacility] = useState('');
    const [referralFacility, setReferralFacility] = useState('');
    const [appliedFilters, setAppliedFilters] = useState({
        date_from: monthStart,
        date_to: today,
        provider: '',
        referring_facility: '',
        referral_facility: '',
    });
    const [report, setReport] = useState<ReportResponse | null>(null);
    const [loading, setLoading] = useState(true);
    const [patientDialogOpen, setPatientDialogOpen] = useState(false);
    const [patientDialogTitle, setPatientDialogTitle] = useState('Referred patients');
    const [patientRows, setPatientRows] = useState<PatientReferral[]>([]);
    const [patientsLoading, setPatientsLoading] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<PatientReferral | null>(null);
    const [deleteConfirmation, setDeleteConfirmation] = useState('');
    const [deleting, setDeleting] = useState(false);

    const loadReport = useCallback(async () => {
        setLoading(true);

        try {
            const response = await axios.get<ReportResponse>('/reports/referrals-by-facility/data', { params: appliedFilters });
            setReport(response.data);
        } catch (error) {
            console.error('Unable to load referral report:', error);
            toast.error('Unable to load the referral report. Check the selected dates and try again.');
        } finally {
            setLoading(false);
        }
    }, [appliedFilters]);

    useEffect(() => {
        void loadReport();
    }, [loadReport]);

    const referringFacilityOptions = useMemo(() => report?.options.referring_facilities ?? [], [report?.options.referring_facilities]);
    const referralFacilityOptions = useMemo(() => report?.options.referral_facilities ?? [], [report?.options.referral_facilities]);
    const providerOptions = useMemo(() => report?.options.providers ?? [], [report?.options.providers]);

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();

        if (dateFrom > dateTo) {
            toast.error('The start date must be on or before the end date.');
            return;
        }

        setAppliedFilters({
            date_from: dateFrom,
            date_to: dateTo,
            provider,
            referring_facility: referringFacility,
            referral_facility: referralFacility,
        });
    };

    const exportUrl = `/reports/referrals-by-facility/csv?${new URLSearchParams(
        Object.entries(appliedFilters).filter(([, value]) => value !== ''),
    ).toString()}`;
    const summary = report?.summary ?? emptySummary;

    const showPatients = async (groupType: 'receiving_facility' | 'rhu', groupCode: string, groupName: string) => {
        setPatientDialogTitle(groupName);
        setPatientRows([]);
        setPatientDialogOpen(true);
        setPatientsLoading(true);

        try {
            const response = await axios.get<{ data: PatientReferral[] }>('/reports/referrals-by-facility/patients', {
                params: { ...appliedFilters, group_type: groupType, group_code: groupCode },
            });
            setPatientRows(response.data.data ?? []);
        } catch (error) {
            console.error('Unable to load referred patients:', error);
            toast.error('Unable to load the patient referral list.');
        } finally {
            setPatientsLoading(false);
        }
    };

    const closeDeleteDialog = () => {
        if (deleting) return;
        setDeleteTarget(null);
        setDeleteConfirmation('');
    };

    const deletePatientTransaction = async () => {
        if (!deleteTarget || deleteConfirmation !== deleteTarget.log_id) return;

        setDeleting(true);

        try {
            await axios.delete(`/referrals/${encodeURIComponent(deleteTarget.log_id)}`);
            setPatientRows((rows) => rows.filter((row) => row.log_id !== deleteTarget.log_id));
            toast.success(`Referral transaction ${deleteTarget.log_id} was deleted.`);
            setDeleteTarget(null);
            setDeleteConfirmation('');
            await loadReport();
        } catch (error) {
            toast.error(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'Unable to delete this referral transaction.')
                    : 'Unable to delete this referral transaction.',
            );
        } finally {
            setDeleting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Referrals by Facility" />
            <div className="space-y-4 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Referrals by Facility</h1>
                        <p className="text-muted-foreground text-sm">
                            Counts referrals sent in the selected period, including RHU referrals, and how many were acknowledged as received.
                        </p>
                    </div>
                    <Button asChild variant="outline">
                        <a href={exportUrl}>
                            <Download className="mr-2 h-4 w-4" /> Export CSV
                        </a>
                    </Button>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <form className="grid gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1.25fr_1.5fr_1.5fr_auto]" onSubmit={submitFilters}>
                            <label className="space-y-1 text-sm font-medium">
                                Date from
                                <Input type="date" value={dateFrom} onChange={(event) => setDateFrom(event.target.value)} required />
                            </label>
                            <label className="space-y-1 text-sm font-medium">
                                Provider
                                <select
                                    className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                    value={provider}
                                    onChange={(event) => setProvider(event.target.value)}
                                >
                                    <option value="">All providers</option>
                                    {providerOptions.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-1 text-sm font-medium">
                                Date to
                                <Input type="date" value={dateTo} onChange={(event) => setDateTo(event.target.value)} required />
                            </label>
                            <label className="space-y-1 text-sm font-medium">
                                Referring facility
                                <select
                                    className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                    value={referringFacility}
                                    onChange={(event) => setReferringFacility(event.target.value)}
                                >
                                    <option value="">All referring facilities</option>
                                    {referringFacilityOptions.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="space-y-1 text-sm font-medium">
                                Referral facility
                                <select
                                    className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                    value={referralFacility}
                                    onChange={(event) => setReferralFacility(event.target.value)}
                                >
                                    <option value="">All referral facilities</option>
                                    {referralFacilityOptions.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <Button className="self-end" type="submit" disabled={loading}>
                                <RefreshCw className={`mr-2 h-4 w-4 ${loading ? 'animate-spin' : ''}`} /> Apply
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <div className="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-8">
                    {[
                        ['Facilities', summary.facilities],
                        ['Sent', summary.sent_count],
                        ['Sent by RHU', summary.rhu_sent_count],
                        ['Received', summary.received_count],
                        ['Pending', summary.pending_count],
                        ['Receipt rate', `${summary.receipt_rate}%`],
                        ['Daily average', summary.average_daily_referrals],
                        ['Busiest day', summary.busiest_day ? `${summary.busiest_day} (${summary.busiest_day_count})` : '—'],
                    ].map(([label, value]) => (
                        <Card key={label}>
                            <CardContent className="p-4">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-[minmax(0,2fr)_minmax(20rem,1fr)]">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle>Daily referral trend</CardTitle>
                            <CardDescription>Sent versus confirmed received referrals for the active filters.</CardDescription>
                            <div className="flex gap-4 pt-1 text-xs">
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-2.5 w-2.5 rounded-full bg-blue-600" /> Sent
                                </span>
                                <span className="inline-flex items-center gap-1.5">
                                    <span className="h-2.5 w-2.5 rounded-full bg-emerald-600" /> Received
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <DailyReferralTrend data={report?.trend ?? []} />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle>Top receiving facilities</CardTitle>
                            <CardDescription>Highest referral volume, limited to the top eight facilities.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <FacilityVolumeChart data={report?.data ?? []} />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle>Facility breakdown</CardTitle>
                        <CardDescription>Received requires a tracking record with a recorded received date.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Receiving facility</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead className="text-right">Sent by RHU</TableHead>
                                    <TableHead className="text-right">Sent</TableHead>
                                    <TableHead className="text-right">Received</TableHead>
                                    <TableHead className="text-right">Pending</TableHead>
                                    <TableHead className="text-right">Receipt rate</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-muted-foreground h-24 text-center">
                                            Loading report…
                                        </TableCell>
                                    </TableRow>
                                ) : report?.data.length ? (
                                    report.data.map((row) => (
                                        <TableRow key={row.facility_code}>
                                            <TableCell className="font-medium">{row.facility_name}</TableCell>
                                            <TableCell>{row.facility_type}</TableCell>
                                            <TableCell>{row.facility_code}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.rhu_sent_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.sent_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.received_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.pending_count}</TableCell>
                                            <TableCell className="text-right">
                                                <Badge variant={row.receipt_rate >= 80 ? 'default' : 'secondary'}>{row.receipt_rate}%</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => void showPatients('receiving_facility', row.facility_code, row.facility_name)}
                                                >
                                                    <Eye className="mr-2 h-4 w-4" /> View patients
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={9} className="text-muted-foreground h-24 text-center">
                                            No referrals were sent during this period.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle>Referrals Sent by RHU</CardTitle>
                        <CardDescription>
                            Referral volume grouped by each originating Rural Health Unit. {summary.rhu_facilities} RHU
                            {summary.rhu_facilities === 1 ? '' : 's'} sent referrals in this period.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Rural Health Unit</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead className="text-right">Destinations</TableHead>
                                    <TableHead className="text-right">Sent</TableHead>
                                    <TableHead className="text-right">Received</TableHead>
                                    <TableHead className="text-right">Pending</TableHead>
                                    <TableHead className="text-right">Receipt rate</TableHead>
                                    <TableHead className="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-muted-foreground h-24 text-center">
                                            Loading RHU report…
                                        </TableCell>
                                    </TableRow>
                                ) : report?.rhu_data.length ? (
                                    report.rhu_data.map((row) => (
                                        <TableRow key={row.rhu_code}>
                                            <TableCell className="font-medium">{row.rhu_name}</TableCell>
                                            <TableCell>{row.rhu_code}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.destination_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.sent_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.received_count}</TableCell>
                                            <TableCell className="text-right tabular-nums">{row.pending_count}</TableCell>
                                            <TableCell className="text-right">
                                                <Badge variant={row.receipt_rate >= 80 ? 'default' : 'secondary'}>{row.receipt_rate}%</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => void showPatients('rhu', row.rhu_code, row.rhu_name)}
                                                >
                                                    <Eye className="mr-2 h-4 w-4" /> View patients
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={8} className="text-muted-foreground h-24 text-center">
                                            No RHU-origin referrals were sent during this period.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Dialog open={patientDialogOpen} onOpenChange={setPatientDialogOpen}>
                    <DialogContent className="max-h-[85vh] w-[calc(100vw-1rem)] max-w-[calc(100vw-1rem)] overflow-hidden sm:max-w-[calc(100vw-2rem)] xl:max-w-7xl">
                        <DialogHeader>
                            <DialogTitle>{patientDialogTitle}</DialogTitle>
                            <DialogDescription>Patients referred during the active report period and their receiving status.</DialogDescription>
                        </DialogHeader>
                        <div className="max-h-[65vh] overflow-auto rounded-md border">
                            <Table className="min-w-[70rem]">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Patient</TableHead>
                                        <TableHead>Referring facility</TableHead>
                                        <TableHead>Referral facility</TableHead>
                                        <TableHead>Referral date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Received date</TableHead>
                                        {canDelete && <TableHead className="text-right">Action</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {patientsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={canDelete ? 7 : 6} className="text-muted-foreground h-24 text-center">
                                                Loading patients…
                                            </TableCell>
                                        </TableRow>
                                    ) : patientRows.length ? (
                                        patientRows.map((patient) => (
                                            <TableRow key={patient.log_id}>
                                                <TableCell className="font-medium">
                                                    <Link
                                                        className="text-primary focus-visible:ring-ring inline-flex flex-col hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:outline-none"
                                                        href={incomingProfileUrl(patient.log_id)}
                                                    >
                                                        <span>{patient.patient_name}</span>
                                                        <span className="text-muted-foreground text-xs font-normal">View profile</span>
                                                    </Link>
                                                </TableCell>
                                                <TableCell>{patient.referring_facility}</TableCell>
                                                <TableCell>{patient.referral_facility}</TableCell>
                                                <TableCell>{patient.referral_date}</TableCell>
                                                <TableCell>
                                                    <Badge variant={patient.received ? 'default' : 'secondary'}>
                                                        {patient.received ? 'Received' : 'Pending'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{patient.received_date ?? '—'}</TableCell>
                                                {canDelete && (
                                                    <TableCell className="text-right">
                                                        <Button
                                                            aria-label={`Delete referral transaction ${patient.log_id}`}
                                                            size="sm"
                                                            variant="destructive"
                                                            onClick={() => {
                                                                setDeleteTarget(patient);
                                                                setDeleteConfirmation('');
                                                            }}
                                                        >
                                                            <Trash2 className="mr-2 h-4 w-4" /> Delete
                                                        </Button>
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={canDelete ? 7 : 6} className="text-muted-foreground h-24 text-center">
                                                No patients found for this row.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </DialogContent>
                </Dialog>

                <Dialog open={deleteTarget !== null} onOpenChange={(open) => !open && closeDeleteDialog()}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete patient transaction?</DialogTitle>
                            <DialogDescription>
                                This permanently deletes the referral and all related records and attachments. Enter the LogID to confirm.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-2">
                            <label className="text-sm font-medium" htmlFor="delete-referral-log-id">
                                LogID: <span className="font-mono">{deleteTarget?.log_id}</span>
                            </label>
                            <Input
                                id="delete-referral-log-id"
                                autoComplete="off"
                                value={deleteConfirmation}
                                onChange={(event) => setDeleteConfirmation(event.target.value)}
                                placeholder={deleteTarget?.log_id}
                            />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeDeleteDialog} disabled={deleting}>
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={() => void deletePatientTransaction()}
                                disabled={deleting || !deleteTarget || deleteConfirmation !== deleteTarget.log_id}
                            >
                                <Trash2 className="mr-2 h-4 w-4" /> {deleting ? 'Deleting…' : 'Delete transaction'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
