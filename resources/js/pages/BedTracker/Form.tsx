import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import { BedDouble, LoaderCircle, Save, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { type BedTrackerDetail, type FacilityOption } from './types';

type Props = {
    canCreate: boolean;
    canEdit: boolean;
    recordId?: number | null;
    onSaved: () => void;
    onCancel?: () => void;
};

type FormState = {
    facility_hfhudcode: string;
    bed_type: string;
    total_beds: number;
    occupied_beds: number;
    reserved_beds: number;
    remarks: string;
    status: boolean;
};

const defaultState: FormState = {
    facility_hfhudcode: '',
    bed_type: '',
    total_beds: 0,
    occupied_beds: 0,
    reserved_beds: 0,
    remarks: '',
    status: true,
};

export default function BedTrackerForm({ canCreate, canEdit, recordId, onSaved, onCancel }: Props) {
    const isEditMode = recordId !== null && recordId !== undefined;
    const canSubmit = isEditMode ? canEdit : canCreate;
    const bedTypeRef = useRef<HTMLInputElement>(null);

    const [data, setData] = useState<FormState>(defaultState);
    const [facilities, setFacilities] = useState<FacilityOption[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [loadingRecord, setLoadingRecord] = useState(false);
    const [saving, setSaving] = useState(false);

    const availableBeds = useMemo(() => Math.max((Number(data.total_beds) || 0) - (Number(data.occupied_beds) || 0) - (Number(data.reserved_beds) || 0), 0), [data]);
    const occupancyRate = useMemo(() => {
        const total = Number(data.total_beds) || 0;
        const occupied = Number(data.occupied_beds) || 0;
        return total > 0 ? ((occupied / total) * 100).toFixed(2) : '0.00';
    }, [data.occupied_beds, data.total_beds]);

    useEffect(() => {
        bedTypeRef.current?.focus();
    }, [recordId]);

    useEffect(() => {
        const loadFacilities = async () => {
            try {
                const response = await axios.get('/bed-tracker/facilities');
                const rows = response.data.data ?? [];
                setFacilities(rows);

                if (!isEditMode && rows.length === 1) {
                    setData((current) => ({ ...current, facility_hfhudcode: rows[0].hfhudcode }));
                }
            } catch (error) {
                console.error('Unable to load facility options:', error);
                toast.error('Unable to load accessible facilities.');
            }
        };

        void loadFacilities();
    }, [isEditMode]);

    useEffect(() => {
        if (!isEditMode) {
            setData((current) => ({ ...defaultState, facility_hfhudcode: current.facility_hfhudcode }));
            setFieldErrors({});
            return;
        }

        const loadRecord = async () => {
            try {
                setLoadingRecord(true);
                const response = await axios.get(`/bed-tracker/info/${recordId}`);
                const record = (response.data.data ?? {}) as BedTrackerDetail;

                setData({
                    facility_hfhudcode: record.facility_hfhudcode ?? '',
                    bed_type: record.bed_type ?? '',
                    total_beds: Number(record.total_beds ?? 0),
                    occupied_beds: Number(record.occupied_beds ?? 0),
                    reserved_beds: Number(record.reserved_beds ?? 0),
                    remarks: record.remarks ?? '',
                    status: record.status === 'A',
                });
                setFieldErrors({});
            } catch (error) {
                console.error('Unable to load bed tracker record:', error);
                toast.error('Unable to load the selected bed tracker record.');
            } finally {
                setLoadingRecord(false);
            }
        };

        void loadRecord();
    }, [isEditMode, recordId]);

    const setValue = <K extends keyof FormState>(key: K, value: FormState[K]) => {
        setData((current) => ({ ...current, [key]: value }));
        setFieldErrors((current) => ({ ...current, [key]: '', form: '' }));
    };

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        try {
            setSaving(true);
            setFieldErrors({});

            const payload = {
                facility_hfhudcode: data.facility_hfhudcode,
                bed_type: data.bed_type.trim(),
                total_beds: Number(data.total_beds),
                occupied_beds: Number(data.occupied_beds),
                reserved_beds: Number(data.reserved_beds),
                remarks: data.remarks.trim() || null,
                status: data.status ? 'A' : 'I',
            };

            if (isEditMode) {
                await axios.put(`/bed-tracker/update/${recordId}`, payload);
                toast.success('Bed tracker record updated.');
            } else {
                await axios.post('/bed-tracker/store', payload);
                toast.success('Bed tracker record created.');
                setData((current) => ({ ...defaultState, facility_hfhudcode: facilities.length === 1 ? facilities[0].hfhudcode : current.facility_hfhudcode }));
            }

            onSaved();
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const validationErrors = error.response.data.errors ?? {};
                const normalizedErrors = Object.fromEntries(
                    Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
                ) as Record<string, string>;
                setFieldErrors(normalizedErrors);
                toast.error('Please review the bed tracker form.');
                return;
            }

            console.error('Unable to save bed tracker record:', error);
            toast.error('Unable to save bed tracker record right now.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="w-full">
            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <div className="rounded-2xl bg-sky-50 p-3 text-sky-700">
                            <BedDouble className="size-5" />
                        </div>
                        <div>
                            <h2 className="text-lg font-semibold">{isEditMode ? 'Edit Bed Tracker' : 'Create Bed Tracker'}</h2>
                            <p className="text-muted-foreground text-xs">Facility bed status record</p>
                        </div>
                    </div>
                    <Badge variant={data.status ? 'default' : 'outline'}>{data.status ? 'Active' : 'Inactive'}</Badge>
                </div>

                <HeadingSmall title="Bed Capacity" description="Capture total, occupied, and reserved beds for a specific facility and bed category." />

                {loadingRecord ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-16 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Loading bed tracker record...
                    </div>
                ) : (
                    <form className="mt-5 space-y-4" onSubmit={handleSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="facility_hfhudcode">Facility</Label>
                            <select
                                id="facility_hfhudcode"
                                value={data.facility_hfhudcode}
                                onChange={(event) => setValue('facility_hfhudcode', event.target.value)}
                                disabled={facilities.length === 1 || saving}
                                className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <option value="">Select facility</option>
                                {facilities.map((facility) => (
                                    <option key={facility.hfhudcode} value={facility.hfhudcode}>
                                        {facility.facility_name}
                                    </option>
                                ))}
                            </select>
                            <InputError message={fieldErrors.facility_hfhudcode} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="bed_type">Bed type</Label>
                            <Input
                                id="bed_type"
                                ref={bedTypeRef}
                                value={data.bed_type}
                                onChange={(event) => setValue('bed_type', event.target.value.toUpperCase())}
                                placeholder="ER, ICU, WARD, ISOLATION"
                                autoComplete="off"
                            />
                            <InputError message={fieldErrors.bed_type} />
                        </div>

                        <div className="grid gap-4 md:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="total_beds">Total beds</Label>
                                <Input id="total_beds" type="number" min="0" value={data.total_beds} onChange={(event) => setValue('total_beds', Number(event.target.value))} />
                                <InputError message={fieldErrors.total_beds} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="occupied_beds">Occupied beds</Label>
                                <Input id="occupied_beds" type="number" min="0" value={data.occupied_beds} onChange={(event) => setValue('occupied_beds', Number(event.target.value))} />
                                <InputError message={fieldErrors.occupied_beds} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reserved_beds">Reserved beds</Label>
                                <Input id="reserved_beds" type="number" min="0" value={data.reserved_beds} onChange={(event) => setValue('reserved_beds', Number(event.target.value))} />
                                <InputError message={fieldErrors.reserved_beds} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl border bg-slate-50 p-4">
                                <p className="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">Available Beds</p>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{availableBeds}</p>
                            </div>

                            <div className="rounded-xl border bg-slate-50 p-4">
                                <p className="text-xs font-medium tracking-[0.18em] text-slate-500 uppercase">Occupancy Rate</p>
                                <p className="mt-2 text-2xl font-semibold text-slate-900">{occupancyRate}%</p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="remarks">Remarks</Label>
                            <Textarea id="remarks" value={data.remarks} onChange={(event) => setValue('remarks', event.target.value)} rows={4} />
                            <InputError message={fieldErrors.remarks} />
                        </div>

                        <div className="flex items-center justify-between rounded-xl border px-4 py-3">
                            <div>
                                <p className="text-sm font-medium">Active status</p>
                                <p className="text-muted-foreground text-xs">Inactive records stay available for history but can be filtered out.</p>
                            </div>
                            <Switch checked={data.status} onCheckedChange={(checked) => setValue('status', checked)} />
                        </div>
                        <InputError message={fieldErrors.status || fieldErrors.form} />

                        <div className="flex gap-3">
                            <Button type="submit" className="flex-1" disabled={!canSubmit || saving}>
                                {saving ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                                {isEditMode ? 'Save changes' : 'Create record'}
                            </Button>

                            {isEditMode && (
                                <Button type="button" variant="outline" className="flex-1" onClick={onCancel} disabled={saving}>
                                    <X className="size-4" />
                                    Cancel
                                </Button>
                            )}
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
