import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { forwardReferral, loadJourney, pathwayOptions, type ForwardPayload, type Journey, type JourneyOptions } from '@/services/referral-pathway';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

export default function ReferralJourney({ journey, selectedLogId }: { journey: Journey; selectedLogId: string }) {
    const [current, setCurrent] = useState(journey);
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [search, setSearch] = useState('');
    const [options, setOptions] = useState<JourneyOptions>({ facilities: [], reasons: [] });
    const [optionsLoading, setOptionsLoading] = useState(false);
    const [error, setError] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [form, setForm] = useState<ForwardPayload>({
        LogID: journey.current_LogID,
        request_id: '',
        facility_to: '',
        reason: '',
        other_reason: '',
        remarks: '',
        referring_provider: '',
        contact_number: '',
        clinical_update: '',
    });
    const latest = current.transactions[current.transactions.length - 1];
    useEffect(() => {
        if (!open) return;
        const controller = new AbortController();
        setOptionsLoading(true);
        const timer = window.setTimeout(() => {
            void pathwayOptions(search, controller.signal)
                .then(setOptions)
                .catch((cause: unknown) => {
                    if (!axios.isCancel(cause)) setError('Unable to load facilities. Change the search to retry.');
                })
                .finally(() => {
                    if (!controller.signal.aborted) setOptionsLoading(false);
                });
        }, 250);
        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [open, search]);

    const refresh = async () => {
        setRefreshing(true);
        try {
            const next = await loadJourney(selectedLogId);
            setCurrent(next);
            if (next.current_LogID !== current.current_LogID) {
                setSubmitted(false);
                setForm((previous) => ({
                    ...previous,
                    LogID: next.current_LogID,
                    request_id: '',
                    facility_to: '',
                    reason: '',
                    remarks: '',
                    other_reason: '',
                    clinical_update: '',
                }));
            }
        } catch {
            setError('Unable to refresh the journey. Use Refresh to retry.');
        } finally {
            setRefreshing(false);
        }
    };
    const update = (key: keyof ForwardPayload, value: string) => {
        setForm((previous) => ({ ...previous, [key]: value }));
        setErrors((previous) => ({ ...previous, [key]: '' }));
    };
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Incoming', href: '/incoming' },
                { title: 'Referral journey', href: `/referrals/pathway/view?LogID=${encodeURIComponent(selectedLogId)}` },
            ]}
        >
            <Head title="Referral Journey" />
            <div className="mx-auto w-full max-w-6xl space-y-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Referral journey</h1>
                        <p className="text-muted-foreground text-sm">Original referral: {current.root_LogID}</p>
                        <p className="text-sm">
                            Current receiving facility: <strong>{latest.destination.name}</strong>
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" disabled={busy || refreshing} onClick={() => void refresh()}>
                            Refresh
                        </Button>
                        {current.can_forward && !submitted && (
                            <Button
                                disabled={busy || refreshing}
                                onClick={() => {
                                    setForm((previous) => ({
                                        ...previous,
                                        LogID: current.current_LogID,
                                        request_id: previous.request_id || crypto.randomUUID(),
                                    }));
                                    setOpen(!open);
                                }}
                            >
                                {open ? 'Close form' : 'Forward / Refer again'}
                            </Button>
                        )}
                    </div>
                </div>
                {error && (
                    <p role="alert" className="text-destructive text-sm">
                        {error}
                    </p>
                )}
                {open && current.can_forward && !submitted && (
                    <form
                        className="space-y-3 rounded-xl border p-4"
                        onSubmit={async (event) => {
                            event.preventDefault();
                            if (busy) return;
                            setBusy(true);
                            setErrors({});
                            setError('');
                            try {
                                await forwardReferral(form);
                                setSubmitted(true);
                                setOpen(false);
                                toast.success('Referral forwarded. Previous transactions are preserved.');
                                await refresh();
                            } catch (cause: unknown) {
                                if (axios.isAxiosError(cause)) {
                                    const fieldErrors = cause.response?.data?.errors ?? {};
                                    setErrors(
                                        Object.fromEntries(
                                            Object.entries(fieldErrors).map(([key, messages]) => [
                                                key,
                                                Array.isArray(messages) ? String(messages[0]) : String(messages),
                                            ]),
                                        ),
                                    );
                                    setError(cause.response?.data?.message ?? 'Unable to forward. Retry with the same form to avoid duplicates.');
                                } else setError('Unable to forward referral.');
                            } finally {
                                setBusy(false);
                            }
                        }}
                    >
                        <h2 className="font-semibold">Forward from {latest.destination.name}</h2>
                        <p className="text-muted-foreground text-sm">
                            Patient details and the previous clinical information will be carried forward. Review them below and add current findings
                            before sending.
                        </p>
                        <fieldset disabled={busy} className="grid gap-3 md:grid-cols-2">
                            <div className="space-y-1">
                                <Label htmlFor="facility-search">Find receiving facility</Label>
                                <Input
                                    id="facility-search"
                                    value={search}
                                    onChange={(event) => {
                                        setSearch(event.target.value);
                                        update('facility_to', '');
                                    }}
                                    placeholder="Search active facilities (first 50 matches)"
                                />
                                <select
                                    aria-label="Destination facility"
                                    required
                                    className="bg-background h-9 w-full rounded border px-2 text-sm"
                                    value={form.facility_to}
                                    onChange={(event) => update('facility_to', event.target.value)}
                                    disabled={optionsLoading}
                                >
                                    <option value="">{optionsLoading ? 'Loading...' : 'Select destination'}</option>
                                    {options.facilities
                                        .filter((facility) => facility.hfhudcode !== latest.destination.code)
                                        .map((facility) => (
                                            <option key={facility.hfhudcode} value={facility.hfhudcode}>
                                                {facility.facility_name}
                                            </option>
                                        ))}
                                </select>
                                <InputError message={errors.facility_to} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="forward-reason">Reason</Label>
                                <select
                                    id="forward-reason"
                                    required
                                    className="bg-background h-9 w-full rounded border px-2 text-sm"
                                    value={form.reason}
                                    onChange={(event) => update('reason', event.target.value)}
                                >
                                    <option value="">Select reason</option>
                                    {options.reasons.map((reason) => (
                                        <option key={reason.code} value={reason.code}>
                                            {reason.description}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.reason} />
                                <Input
                                    aria-label="Other reason"
                                    maxLength={50}
                                    placeholder="Other reason, if applicable"
                                    value={form.other_reason}
                                    onChange={(event) => update('other_reason', event.target.value)}
                                />
                                <InputError message={errors.other_reason} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="forward-provider">Referring provider</Label>
                                <Input
                                    id="forward-provider"
                                    required
                                    maxLength={180}
                                    value={form.referring_provider}
                                    onChange={(event) => update('referring_provider', event.target.value)}
                                />
                                <InputError message={errors.referring_provider} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="forward-contact">Contact number</Label>
                                <Input
                                    id="forward-contact"
                                    required
                                    maxLength={50}
                                    value={form.contact_number}
                                    onChange={(event) => update('contact_number', event.target.value)}
                                />
                                <InputError message={errors.contact_number} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="forward-notes">Referral / handoff details</Label>
                                <Textarea
                                    id="forward-notes"
                                    required
                                    maxLength={5000}
                                    value={form.remarks}
                                    onChange={(event) => update('remarks', event.target.value)}
                                />
                                <InputError message={errors.remarks} />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="forward-clinical">Updated clinical findings</Label>
                                <Textarea
                                    id="forward-clinical"
                                    maxLength={10000}
                                    value={form.clinical_update}
                                    onChange={(event) => update('clinical_update', event.target.value)}
                                />
                                <InputError message={errors.clinical_update} />
                            </div>
                        </fieldset>
                        <Button disabled={busy || optionsLoading}>{busy ? 'Forwarding...' : 'Send referral to selected facility'}</Button>
                    </form>
                )}
                <ol className="space-y-3" aria-label="Referral pathway">
                    {current.transactions.map((step) => (
                        <li key={step.LogID} className="space-y-3 rounded-xl border p-4">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 className="font-semibold">
                                    {step.sequence}. {step.source.name} → {step.destination.name}
                                </h2>
                                <Badge variant={step.status === 'CANCELLED' ? 'destructive' : 'secondary'}>{step.status}</Badge>
                            </div>
                            <p className="text-muted-foreground text-xs">
                                {step.LogID} · Referred: {step.referred_at || 'Not recorded'}
                            </p>
                            <div className="flex flex-wrap gap-x-5 gap-y-1 text-xs">
                                {[
                                    ['Received', step.received_at],
                                    ['Admitted', step.admitted_at],
                                    ['Discharged', step.discharged_at],
                                    ['Forwarded', step.forwarded_at],
                                ].map(
                                    ([label, date]) =>
                                        date && (
                                            <span key={label}>
                                                {label}: {date}
                                            </span>
                                        ),
                                )}
                            </div>
                            <p className="text-sm whitespace-pre-wrap">{step.details.remarks || 'No referral notes recorded.'}</p>
                            <details className="text-sm">
                                <summary className="cursor-pointer font-medium">Referral and clinical details</summary>
                                <div className="mt-3 grid gap-3 md:grid-cols-2">
                                    <p>
                                        Reason: {step.details.referralReason || '—'} · Category: {step.details.referralCategory || '—'}
                                    </p>
                                    <p>
                                        Provider: {step.details.referringProvider || '—'} · {step.details.referringProviderContactNumber}
                                    </p>
                                    <p className="whitespace-pre-wrap">Diagnosis: {step.clinical?.clinicalDiagnosis || '—'}</p>
                                    <p className="whitespace-pre-wrap">Chief complaint: {step.clinical?.chiefComplaint || '—'}</p>
                                    <p className="whitespace-pre-wrap">Clinical history: {step.clinical?.clinicalHistory || '—'}</p>
                                    <p className="whitespace-pre-wrap">Findings: {step.clinical?.findings || '—'}</p>
                                </div>
                                {step.status_history.map((event, index) => (
                                    <p className="mt-2" key={index}>
                                        {event.created_at} · {event.referral_status} · {event.remarks}
                                    </p>
                                ))}
                            </details>
                            {step.cancellation && (
                                <p className="text-destructive text-sm">
                                    Cancelled {step.cancellation.cancelled_at}: {step.cancellation.reason}
                                </p>
                            )}
                        </li>
                    ))}
                </ol>
            </div>
        </AppLayout>
    );
}
