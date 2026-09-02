import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Download, Eye, RefreshCw } from 'lucide-react';
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

type Summary = {
    facilities: number;
    rhu_facilities: number;
    sent_count: number;
    rhu_sent_count: number;
    received_count: number;
    pending_count: number;
    receipt_rate: number;
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

const emptySummary: Summary = {
    facilities: 0,
    rhu_facilities: 0,
    sent_count: 0,
    rhu_sent_count: 0,
    received_count: 0,
    pending_count: 0,
    receipt_rate: 0,
};

const breadcrumbs = [{ title: 'Referral Report', href: '/reports/referrals-by-facility' }];

export default function ReferralFacilityReport() {
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

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-6">
                    {[
                        ['Facilities', summary.facilities],
                        ['Sent', summary.sent_count],
                        ['Sent by RHU', summary.rhu_sent_count],
                        ['Received', summary.received_count],
                        ['Pending', summary.pending_count],
                        ['Receipt rate', `${summary.receipt_rate}%`],
                    ].map(([label, value]) => (
                        <Card key={label}>
                            <CardContent className="p-4">
                                <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">{value}</p>
                            </CardContent>
                        </Card>
                    ))}
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
                    <DialogContent className="max-h-[85vh] overflow-hidden sm:max-w-6xl">
                        <DialogHeader>
                            <DialogTitle>{patientDialogTitle}</DialogTitle>
                            <DialogDescription>Patients referred during the active report period and their receiving status.</DialogDescription>
                        </DialogHeader>
                        <div className="max-h-[65vh] overflow-auto rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Patient</TableHead>
                                        <TableHead>Referring facility</TableHead>
                                        <TableHead>Referral facility</TableHead>
                                        <TableHead>Referral date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Received date</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {patientsLoading ? (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground h-24 text-center">
                                                Loading patients…
                                            </TableCell>
                                        </TableRow>
                                    ) : patientRows.length ? (
                                        patientRows.map((patient) => (
                                            <TableRow key={patient.log_id}>
                                                <TableCell className="font-medium">{patient.patient_name}</TableCell>
                                                <TableCell>{patient.referring_facility}</TableCell>
                                                <TableCell>{patient.referral_facility}</TableCell>
                                                <TableCell>{patient.referral_date}</TableCell>
                                                <TableCell>
                                                    <Badge variant={patient.received ? 'default' : 'secondary'}>
                                                        {patient.received ? 'Received' : 'Pending'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>{patient.received_date ?? '—'}</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={6} className="text-muted-foreground h-24 text-center">
                                                No patients found for this row.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
