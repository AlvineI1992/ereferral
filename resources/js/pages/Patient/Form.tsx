import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';
import { HeartPulse, LoaderCircle, Save, UserRound, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import DemographicSelector from '../Demographics/Demographics_selector';
import { type PatientDetail, type ReligionOption } from './types';

type Props = {
    canCreate: boolean;
    canEdit: boolean;
    patientId?: number | null;
    onCreated: () => void;
    onCancel?: () => void;
};

type FormState = {
    legacy_log_id: string;
    family_id: string;
    phic_number: string;
    case_number: string;
    last_name: string;
    first_name: string;
    middle_name: string;
    suffix: string;
    birth_date: string;
    sex: string;
    contact_number: string;
    religion: string;
    blood_type: string;
    blood_type_rh: string;
    civil_status: string;
    street_address: string;
    region_code: string;
    province_code: string;
    city_code: string;
    barangay_code: string;
    zip_code: string;
};

const bloodTypeOptions = ['A', 'B', 'AB', 'O'];
const bloodRhOptions = ['+', '-'];
const sexOptions = [
    { value: 'M', label: 'Male' },
    { value: 'F', label: 'Female' },
];
const civilStatusOptions = [
    { value: 'S', label: 'Single' },
    { value: 'M', label: 'Married' },
    { value: 'W', label: 'Widowed' },
    { value: 'Y', label: 'Separated (in Fact)' },
    { value: 'D', label: 'Divorced' },
    { value: 'A', label: 'Annulled' },
    { value: 'C', label: 'Co-Habitation' },
    { value: 'X', label: 'Legally Separated' },
    { value: 'N', label: 'Not Stated' },
    { value: 'U', label: 'Unknown' },
];

const defaultData: FormState = {
    legacy_log_id: '',
    family_id: '',
    phic_number: '',
    case_number: '',
    last_name: '',
    first_name: '',
    middle_name: '',
    suffix: '',
    birth_date: '',
    sex: '',
    contact_number: '',
    religion: '',
    blood_type: '',
    blood_type_rh: '',
    civil_status: '',
    street_address: '',
    region_code: '',
    province_code: '',
    city_code: '',
    barangay_code: '',
    zip_code: '',
};

export default function PatientForm({ canCreate, canEdit, patientId, onCreated, onCancel }: Props) {
    const isEditMode = patientId !== null && patientId !== undefined;
    const canSubmit = isEditMode ? canEdit : canCreate;
    const firstNameRef = useRef<HTMLInputElement>(null);

    const [data, setData] = useState<FormState>(defaultData);
    const [religions, setReligions] = useState<ReligionOption[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [loadingRecord, setLoadingRecord] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        firstNameRef.current?.focus();
    }, [patientId]);

    useEffect(() => {
        const loadReligions = async () => {
            try {
                const response = await axios.get('/api/religions', {
                    withCredentials: true,
                    headers: {
                        Accept: 'application/json',
                    },
                });

                setReligions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load religions:', error);
            }
        };

        void loadReligions();
    }, []);

    useEffect(() => {
        if (!isEditMode) {
            setData(defaultData);
            setFieldErrors({});
            return;
        }

        const loadPatient = async () => {
            try {
                setLoadingRecord(true);
                const response = await axios.get(`/patient-registry/info/${patientId}`);
                const record = (response.data.data ?? {}) as PatientDetail;

                setData({
                    legacy_log_id: record.legacy_log_id ?? '',
                    family_id: record.family_id ?? '',
                    phic_number: record.phic_number ?? '',
                    case_number: record.case_number ?? '',
                    last_name: record.last_name ?? '',
                    first_name: record.first_name ?? '',
                    middle_name: record.middle_name ?? '',
                    suffix: record.suffix ?? '',
                    birth_date: record.birth_date ?? '',
                    sex: record.sex ?? '',
                    contact_number: record.contact_number ?? '',
                    religion: record.religion ?? '',
                    blood_type: record.blood_type ?? '',
                    blood_type_rh: record.blood_type_rh ?? '',
                    civil_status: record.civil_status ?? '',
                    street_address: record.street_address ?? '',
                    region_code: record.region_code ?? '',
                    province_code: record.province_code ?? '',
                    city_code: record.city_code ?? '',
                    barangay_code: record.barangay_code ?? '',
                    zip_code: record.zip_code ?? '',
                });
                setFieldErrors({});
            } catch (error) {
                console.error('Unable to load patient record:', error);
                toast.error('Unable to load the selected patient.');
            } finally {
                setLoadingRecord(false);
            }
        };

        void loadPatient();
    }, [isEditMode, patientId]);

    const religionOptions = useMemo(() => {
        const options = religions.map((item) => ({ value: item.relcode, label: item.reldesc }));

        if (data.religion && !options.some((item) => item.value === data.religion)) {
            options.unshift({ value: data.religion, label: data.religion });
        }

        return options;
    }, [data.religion, religions]);

    const setValue = <K extends keyof FormState>(key: K, value: FormState[K]) => {
        setData((current) => ({ ...current, [key]: value }));
        setFieldErrors((current) => ({ ...current, [key]: '', form: '' }));
    };

    const handleInputChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        setValue(event.target.id as keyof FormState, event.target.value as never);
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
                legacy_log_id: data.legacy_log_id.trim() || null,
                family_id: data.family_id.trim() || null,
                phic_number: data.phic_number.trim() || null,
                case_number: data.case_number.trim() || null,
                last_name: data.last_name.trim(),
                first_name: data.first_name.trim(),
                middle_name: data.middle_name.trim() || null,
                suffix: data.suffix.trim() || null,
                birth_date: data.birth_date || null,
                sex: data.sex || null,
                contact_number: data.contact_number.trim() || null,
                religion: data.religion || null,
                blood_type: data.blood_type || null,
                blood_type_rh: data.blood_type_rh || null,
                civil_status: data.civil_status || null,
                street_address: data.street_address.trim() || null,
                region_code: data.region_code || null,
                province_code: data.province_code || null,
                city_code: data.city_code || null,
                barangay_code: data.barangay_code || null,
                zip_code: data.zip_code.trim() || null,
            };

            if (isEditMode) {
                await axios.put(`/patient-registry/update/${patientId}`, payload);
                toast.success('Patient updated.');
            } else {
                await axios.post('/patient-registry/store', payload);
                toast.success('Patient created.');
                setData(defaultData);
            }

            onCreated();
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const validationErrors = error.response.data.errors ?? {};
                const normalizedErrors = Object.fromEntries(
                    Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
                ) as Record<string, string>;
                setFieldErrors(normalizedErrors);
                toast.error('Please review the patient form.');
                return;
            }

            console.error('Unable to save patient:', error);
            toast.error('Unable to save patient right now.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="w-full">
            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                <div className="mb-4 flex items-center gap-3">
                    <div className="rounded-2xl bg-emerald-50 p-3 text-emerald-700">
                        {isEditMode ? <HeartPulse className="size-5" /> : <UserRound className="size-5" />}
                    </div>
                    <div>
                        <h2 className="text-lg font-semibold">{isEditMode ? 'Edit Patient' : 'Register Patient'}</h2>
                        <p className="text-muted-foreground text-xs">Patient master list form</p>
                    </div>
                </div>

                <HeadingSmall title="Patient Details" description="Maintain identity, coverage, and demographics in one patient master record." />

                {loadingRecord ? (
                    <div className="text-muted-foreground flex items-center justify-center gap-2 py-16 text-sm">
                        <LoaderCircle className="size-4 animate-spin" />
                        Loading patient record...
                    </div>
                ) : (
                    <form className="mt-5 space-y-5" onSubmit={handleSubmit}>
                        <section className="space-y-4">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">Identity</p>
                                <p className="text-muted-foreground text-xs">Core identity fields used for duplicate detection.</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="last_name">Last name</Label>
                                    <Input id="last_name" value={data.last_name} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.last_name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="first_name">First name</Label>
                                    <Input id="first_name" ref={firstNameRef} value={data.first_name} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.first_name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="middle_name">Middle name</Label>
                                    <Input id="middle_name" value={data.middle_name} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.middle_name} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="suffix">Suffix</Label>
                                    <Input id="suffix" value={data.suffix} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.suffix} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="birth_date">Birth date</Label>
                                    <Input id="birth_date" type="date" value={data.birth_date} onChange={handleInputChange} />
                                    <InputError message={fieldErrors.birth_date} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="sex">Sex</Label>
                                    <select
                                        id="sex"
                                        value={data.sex}
                                        onChange={handleInputChange}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <option value="">Select sex</option>
                                        {sexOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.sex} />
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="civil_status">Civil status</Label>
                                    <select
                                        id="civil_status"
                                        value={data.civil_status}
                                        onChange={handleInputChange}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <option value="">Select civil status</option>
                                        {civilStatusOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.civil_status} />
                                </div>
                            </div>
                        </section>

                        <section className="space-y-4">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">Coverage And Contact</p>
                                <p className="text-muted-foreground text-xs">Insurance, case tracking, and religion reference values.</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="family_id">Family number</Label>
                                    <Input id="family_id" value={data.family_id} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.family_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="phic_number">PHIC number</Label>
                                    <Input id="phic_number" value={data.phic_number} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.phic_number} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="case_number">Case number</Label>
                                    <Input id="case_number" value={data.case_number} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.case_number} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="contact_number">Contact number</Label>
                                    <Input id="contact_number" value={data.contact_number} onChange={handleInputChange} autoComplete="off" />
                                    <InputError message={fieldErrors.contact_number} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="religion">Religion</Label>
                                    <select
                                        id="religion"
                                        value={data.religion}
                                        onChange={handleInputChange}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <option value="">Select religion</option>
                                        {religionOptions.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.religion} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="blood_type">Blood type</Label>
                                    <select
                                        id="blood_type"
                                        value={data.blood_type}
                                        onChange={handleInputChange}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <option value="">Select blood type</option>
                                        {bloodTypeOptions.map((value) => (
                                            <option key={value} value={value}>
                                                {value}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.blood_type} />
                                </div>

                                <div className="space-y-2 md:col-span-2">
                                    <Label htmlFor="blood_type_rh">RH factor</Label>
                                    <select
                                        id="blood_type_rh"
                                        value={data.blood_type_rh}
                                        onChange={handleInputChange}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    >
                                        <option value="">Select RH factor</option>
                                        {bloodRhOptions.map((value) => (
                                            <option key={value} value={value}>
                                                {value}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.blood_type_rh} />
                                </div>
                            </div>
                        </section>

                        <section className="space-y-4">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">Demographics</p>
                                <p className="text-muted-foreground text-xs">Address and Philippine location codes used across intake and referral workflows.</p>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="street_address">Street address</Label>
                                <Input id="street_address" value={data.street_address} onChange={handleInputChange} autoComplete="off" />
                                <InputError message={fieldErrors.street_address} />
                            </div>

                            <DemographicSelector
                                variant="vertical"
                                value={{
                                    region: data.region_code,
                                    province: data.province_code,
                                    city: data.city_code,
                                    barangay: data.barangay_code,
                                }}
                                onChange={(value) => {
                                    setValue('region_code', value.region || '');
                                    setValue('province_code', value.province || '');
                                    setValue('city_code', value.city || '');
                                    setValue('barangay_code', value.barangay || '');
                                }}
                                errors={{
                                    region: fieldErrors.region_code,
                                    province: fieldErrors.province_code,
                                    city: fieldErrors.city_code,
                                    barangay: fieldErrors.barangay_code,
                                }}
                            />

                            <div className="space-y-2">
                                <Label htmlFor="zip_code">Zip code</Label>
                                <Input id="zip_code" value={data.zip_code} onChange={handleInputChange} autoComplete="off" />
                                <InputError message={fieldErrors.zip_code} />
                            </div>
                        </section>

                        <InputError message={fieldErrors.form} />

                        <div className="flex gap-3">
                            <Button type="submit" className="flex-1" disabled={!canSubmit || saving}>
                                {saving ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                                {isEditMode ? 'Save changes' : 'Create patient'}
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
