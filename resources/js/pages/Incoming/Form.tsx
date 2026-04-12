import PatientAvatar from '@/components/PatientAvatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FloatingInput, FloatingSelect, FloatingTextarea } from '@/components/ui/FloatingInput';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Check, ChevronLeft, ChevronRight, ClipboardList, HeartPulse, LoaderCircle, Map, Save, User } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import DemographicSelector from '../Demographics/Demographics_selector';
import type { HospitalOption } from '../Ref_Facilities/HospitalSelector';
import ReferralForm from './ReferralForm';

const STEPS = [
    { label: 'Patient', icon: User },
    { label: 'Demographics', icon: Map },
    { label: 'Referral', icon: ClipboardList },
    { label: 'Clinical', icon: HeartPulse },
    { label: 'Review', icon: Check },
];

type FormType = {
    profilePic: File | null;
    patientFirstName: string;
    patientMiddleName: string;
    patientLastName: string;
    patientSuffix: string;
    patientBirthDate: string;
    patientSex: string;
    patientCivilStatus: string;
    patientContactNumber: string;
    familyNumber: string;
    caseNumber: string;
    phicNumber: string;
    religion: string;
    bloodType: string;
    bloodRh: string;
    patientStreetAddress: string;
    region: string;
    province: string;
    city: string;
    barangay: string;
    zipcode: string;
    calledDate: string;
    refferalDate: string;
    referringFacility: string;
    referralFacility: string;
    transactionCode: string;
    typeOfReferral: string;
    referralCategory: string;
    referralReason: string;
    otherReferralReason: string;
    contactPerson: string;
    contactDesignation: string;
    referralContactNumber: string;
    referralRemarks: string;
    diagnosis: string;
    chiefComplaint: string;
    clinicalHistory: string;
    findings: string;
    providerFirstName: string;
    providerMiddleName: string;
    providerLastName: string;
    providerSuffix: string;
    bp: string;
    temp: string;
    hr: string;
    rr: string;
    o2Sats: string;
    weight: string;
    height: string;
};

type ReligionOption = {
    relcode: string;
    reldesc: string;
};

type ReferralFormPageProps = {
    id?: string;
    mode?: 'create' | 'edit';
};

const bloodTypeOptions = ['A', 'B', 'AB', 'O'].map((value) => ({ value, label: value }));
const bloodRhOptions = [
    { value: '+', label: 'Positive (+)' },
    { value: '-', label: 'Negative (-)' },
];
const civilStatusOptions = ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'].map((value) => ({ value, label: value }));
const sexOptions = [
    { value: 'M', label: 'Male' },
    { value: 'F', label: 'Female' },
];

const toDateTimeLocal = (date: Date) => {
    const offset = date.getTimezoneOffset();
    return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
};

const splitItems = (value: string) =>
    value
        .split(/[\r\n,]+/)
        .map((item) => item.trim())
        .filter(Boolean);
const formatDateTime = (value: string) => (value ? new Date(value).toLocaleString() : 'Not set');

const ReviewRow = ({ label, value }: { label: string; value?: string | null }) => (
    <div className="flex items-start justify-between gap-4 px-4 py-3">
        <span className="text-muted-foreground text-sm">{label}</span>
        <span className="max-w-[62%] text-right text-sm font-medium text-slate-900 dark:text-slate-100">{value || 'Not provided'}</span>
    </div>
);

const defaultFormData: FormType = {
    profilePic: null,
    patientFirstName: '',
    patientMiddleName: '',
    patientLastName: '',
    patientSuffix: '',
    patientBirthDate: '',
    patientSex: '',
    patientCivilStatus: '',
    patientContactNumber: '',
    familyNumber: '',
    caseNumber: '',
    phicNumber: '',
    religion: '',
    bloodType: '',
    bloodRh: '',
    patientStreetAddress: '',
    region: '',
    province: '',
    city: '',
    barangay: '',
    zipcode: '',
    calledDate: toDateTimeLocal(new Date()),
    refferalDate: toDateTimeLocal(new Date()),
    referringFacility: '',
    referralFacility: '',
    transactionCode: '',
    typeOfReferral: '',
    referralCategory: '',
    referralReason: '',
    otherReferralReason: '',
    contactPerson: '',
    contactDesignation: '',
    referralContactNumber: '',
    referralRemarks: '',
    diagnosis: '',
    chiefComplaint: '',
    clinicalHistory: '',
    findings: '',
    providerFirstName: '',
    providerMiddleName: '',
    providerLastName: '',
    providerSuffix: '',
    bp: '',
    temp: '',
    hr: '',
    rr: '',
    o2Sats: '',
    weight: '',
    height: '',
};

export default function CreateReferral({ id, mode = 'create' }: ReferralFormPageProps) {
    const isEditMode = mode === 'edit';
    const pageTitle = isEditMode ? 'Edit Referral' : 'Create Referral';
    const breadcrumbs = [
        { title: 'Incoming', href: '/incoming' },
        { title: pageTitle, href: isEditMode && id ? `/referrals/edit/${id}` : '/referrals/create' },
    ];
    const [step, setStep] = useState(0);
    const [stepErrors, setStepErrors] = useState<Record<string, string>>({});
    const [hospitals, setHospitals] = useState<HospitalOption[]>([]);
    const [religions, setReligions] = useState<ReligionOption[]>([]);
    const [loadingReferral, setLoadingReferral] = useState(isEditMode);
    const firstInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, put, processing, errors } = useForm<FormType>(defaultFormData);

    useEffect(() => {
        firstInputRef.current?.focus();
    }, [step]);
    useEffect(() => {
        axios
            .get('/facilities-list')
            .then((response) => setHospitals(Array.isArray(response.data) ? response.data : response.data.data || []))
            .catch(() => toast.error('Unable to load facilities.'));
    }, []);

    useEffect(() => {
        axios
            .get('/api/religions', {
                withCredentials: true,
                headers: {
                    Accept: 'application/json',
                },
            })
            .then((response) => {
                const items = Array.isArray(response.data?.data)
                    ? response.data.data
                    : Array.isArray(response.data)
                      ? response.data
                      : [];

                setReligions(items);
            })
            .catch(() => toast.error('Unable to load religions.'));
    }, []);

    useEffect(() => {
        if (!isEditMode || !id) {
            setLoadingReferral(false);
            return;
        }

        let cancelled = false;
        setLoadingReferral(true);

        axios
            .get(route('incoming.referral.edit-data', { LogID: id }, false))
            .then((response) => {
                if (cancelled) {
                    return;
                }

                const payload = response.data?.data;
                if (!payload) {
                    throw new Error('Missing referral payload.');
                }

                setData((current) => ({
                    ...current,
                    ...payload,
                    profilePic: null,
                }));
            })
            .catch(() => {
                if (!cancelled) {
                    toast.error('Unable to load referral details.');
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoadingReferral(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [id, isEditMode, setData]);

    const mergedErrors = { ...(errors as Record<string, string>), ...stepErrors };
    const generalError = (errors as Record<string, string>).form;
    const selectedOrigin = hospitals.find((item) => item.hfhudcode === data.referringFacility);
    const selectedDestination = hospitals.find((item) => item.hfhudcode === data.referralFacility);
    const selectedReligion = religions.find((item) => item.relcode === data.religion || item.reldesc === data.religion);
    const diagnosisItems = useMemo(() => splitItems(data.diagnosis), [data.diagnosis]);
    const religionOptions = useMemo(() => {
        const options = religions.map((item) => ({ value: item.relcode, label: item.reldesc }));

        if (data.religion && !options.some((item) => item.value === data.religion)) {
            options.unshift({ value: data.religion, label: data.religion });
        }

        return options;
    }, [data.religion, religions]);

    const setValue = (key: keyof FormType, value: FormType[keyof FormType]) => {
        setData(key, value);
        setStepErrors((current) => ({ ...current, [key]: '' }));
    };

    const handleReferralChange = useCallback(
        (key: string, value: string) => {
            setData(key as keyof FormType, value as FormType[keyof FormType]);
            setStepErrors((current) => ({ ...current, [key]: '' }));
        },
        [setData],
    );

    const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        setValue(event.target.id as keyof FormType, event.target.value);
    };

    const validateStep = (currentStep: number) => {
        const next: Record<string, string> = {};
        if (currentStep === 0) {
            if (!data.patientFirstName.trim()) next.patientFirstName = 'First name is required.';
            if (!data.patientLastName.trim()) next.patientLastName = 'Last name is required.';
            if (!data.patientBirthDate) next.patientBirthDate = 'Birth date is required.';
            if (!data.patientSex) next.patientSex = 'Sex is required.';
        }
        if (currentStep === 1) {
            if (!data.patientStreetAddress.trim()) next.patientStreetAddress = 'Street address is required.';
            if (!data.region) next.region = 'Region is required.';
            if (!data.province) next.province = 'Province is required.';
            if (!data.city) next.city = 'City is required.';
            if (!data.barangay) next.barangay = 'Barangay is required.';
            if (!data.zipcode.trim()) next.zipcode = 'Zip code is required.';
        }
        if (currentStep === 2) {
            if (!data.referringFacility) next.referringFacility = 'Referring facility is required.';
            if (!data.referralFacility) next.referralFacility = 'Receiving facility is required.';
            if (data.referringFacility && data.referralFacility && data.referringFacility === data.referralFacility)
                next.referralFacility = 'Choose a different receiving facility.';
            if (!data.refferalDate) next.refferalDate = 'Referral date is required.';
            if (!data.typeOfReferral) next.typeOfReferral = 'Type of referral is required.';
            if (!data.referralCategory) next.referralCategory = 'Referral category is required.';
            if (!data.referralReason) next.referralReason = 'Referral reason is required.';
            if (data.referralReason === 'OTHER' && !data.otherReferralReason.trim()) next.otherReferralReason = 'Specify the referral reason.';
            if (!data.contactPerson.trim()) next.contactPerson = 'Receiving contact person is required.';
        }
        if (currentStep === 3) {
            if (splitItems(data.diagnosis).length === 0) next.diagnosis = 'Add at least one diagnosis.';
            if (!data.chiefComplaint.trim()) next.chiefComplaint = 'Chief complaint is required.';
            if (!data.providerFirstName.trim()) next.providerFirstName = 'Provider first name is required.';
            if (!data.providerLastName.trim()) next.providerLastName = 'Provider last name is required.';
        }
        setStepErrors(next);
        return Object.keys(next).length === 0;
    };

    const goNext = () => validateStep(step) && setStep((current) => Math.min(current + 1, STEPS.length - 1));
    const goBack = () => {
        setStepErrors({});
        setStep((current) => Math.max(current - 1, 0));
    };

    const submit = () => {
        if (!validateStep(3)) {
            setStep(3);
            return;
        }

        const endpoint = isEditMode ? route('incoming.referral.update', { LogID: id }, false) : route('referral.store', undefined, false);
        const submitAction = isEditMode ? put : post;

        submitAction(endpoint, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => toast.success(isEditMode ? 'Referral updated successfully.' : 'Referral created successfully.'),
            onError: (serverErrors) => {
                toast.error('Please check the form for errors.');
                const patient = [
                    'patientFirstName',
                    'patientMiddleName',
                    'patientLastName',
                    'patientSuffix',
                    'patientBirthDate',
                    'patientSex',
                    'patientCivilStatus',
                    'patientContactNumber',
                    'familyNumber',
                    'caseNumber',
                    'phicNumber',
                    'religion',
                    'bloodType',
                    'bloodRh',
                ];
                const demographic = ['patientStreetAddress', 'region', 'province', 'city', 'barangay', 'zipcode'];
                const referral = [
                    'referringFacility',
                    'referralFacility',
                    'calledDate',
                    'refferalDate',
                    'transactionCode',
                    'typeOfReferral',
                    'referralCategory',
                    'referralReason',
                    'otherReferralReason',
                    'contactPerson',
                    'contactDesignation',
                    'referralContactNumber',
                    'referralRemarks',
                ];
                const clinical = [
                    'diagnosis',
                    'chiefComplaint',
                    'clinicalHistory',
                    'findings',
                    'providerFirstName',
                    'providerMiddleName',
                    'providerLastName',
                    'providerSuffix',
                    'bp',
                    'temp',
                    'hr',
                    'rr',
                    'o2Sats',
                    'weight',
                    'height',
                ];
                if (patient.some((key) => serverErrors[key])) return setStep(0);
                if (demographic.some((key) => serverErrors[key])) return setStep(1);
                if (referral.some((key) => serverErrors[key])) return setStep(2);
                if (clinical.some((key) => serverErrors[key])) return setStep(3);
                setStep(4);
            },
        });
    };

    const stepDone = [
        Boolean(data.patientFirstName && data.patientLastName && data.patientBirthDate && data.patientSex),
        Boolean(data.patientStreetAddress && data.region && data.province && data.city && data.barangay && data.zipcode),
        Boolean(
            data.referringFacility &&
            data.referralFacility &&
            data.typeOfReferral &&
            data.referralCategory &&
            data.referralReason &&
            data.contactPerson &&
            data.refferalDate &&
            (data.referralReason !== 'OTHER' || data.otherReferralReason),
        ),
        Boolean(diagnosisItems.length && data.chiefComplaint && data.providerFirstName && data.providerLastName),
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={pageTitle} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <Card className="overflow-hidden shadow-sm">
                        <CardHeader className="border-b bg-slate-50/80 dark:bg-slate-900/50">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <CardTitle>{pageTitle}</CardTitle>
                                    <CardDescription>
                                        {isEditMode
                                            ? 'Update patient, routing, and clinical details for this saved referral.'
                                            : 'Capture patient, handoff, and clinical details before submitting.'}
                                    </CardDescription>
                                </div>
                                <Badge variant="outline" className="rounded-full px-3 py-1">
                                    Step {step + 1} of {STEPS.length}
                                </Badge>
                            </div>
                            <div className="mt-4 flex flex-wrap gap-2">
                                {STEPS.map((item, index) => {
                                    const Icon = item.icon;
                                    const active = index === step;
                                    const done = index < step || stepDone[index];
                                    return (
                                        <button
                                            key={item.label}
                                            type="button"
                                            onClick={() => index <= step && setStep(index)}
                                            className={cn(
                                                'flex items-center gap-2 rounded-full border px-3 py-2 text-sm',
                                                active && 'border-teal-600 bg-teal-50 text-teal-700 dark:bg-teal-950/30',
                                                !active && done && 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20',
                                                !active && !done && 'border-slate-200 text-slate-500 dark:border-slate-800 dark:text-slate-400',
                                            )}
                                        >
                                            <span className="flex h-7 w-7 items-center justify-center rounded-full border bg-white dark:bg-slate-950">
                                                {done && !active ? <Check className="size-4" /> : <Icon className="size-4" />}
                                            </span>
                                            <span className="hidden sm:inline">{item.label}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-6 p-5 md:p-6">
                            {loadingReferral ? (
                                <div className="flex min-h-64 items-center justify-center text-sm text-slate-500">
                                    <LoaderCircle className="mr-2 size-4 animate-spin" />
                                    Loading referral details...
                                </div>
                            ) : (
                                <>
                            {generalError && (
                                <div className="border-destructive/30 bg-destructive/5 text-destructive rounded-2xl border px-4 py-3 text-sm">
                                    {generalError}
                                </div>
                            )}

                            {step === 0 && (
                                <div className="grid gap-5 xl:grid-cols-[220px_minmax(0,1fr)]">
                                    <PatientAvatar
                                        initials={[data.patientFirstName[0], data.patientLastName[0]].filter(Boolean).join('').toUpperCase() || 'PT'}
                                        onCapture={(file) => setValue('profilePic', file)}
                                    />
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <FloatingInput
                                            id="patientFirstName"
                                            ref={firstInputRef}
                                            label="First name"
                                            required
                                            value={data.patientFirstName}
                                            onChange={handleChange}
                                            error={mergedErrors.patientFirstName}
                                        />
                                        <FloatingInput
                                            id="patientMiddleName"
                                            label="Middle name"
                                            value={data.patientMiddleName}
                                            onChange={handleChange}
                                            error={mergedErrors.patientMiddleName}
                                        />
                                        <FloatingInput
                                            id="patientLastName"
                                            label="Last name"
                                            required
                                            value={data.patientLastName}
                                            onChange={handleChange}
                                            error={mergedErrors.patientLastName}
                                        />
                                        <FloatingInput
                                            id="patientSuffix"
                                            label="Suffix"
                                            value={data.patientSuffix}
                                            onChange={handleChange}
                                            error={mergedErrors.patientSuffix}
                                        />
                                        <FloatingInput
                                            id="patientBirthDate"
                                            label="Birth date"
                                            type="date"
                                            required
                                            value={data.patientBirthDate}
                                            onChange={handleChange}
                                            error={mergedErrors.patientBirthDate}
                                        />
                                        <FloatingSelect
                                            id="patientSex"
                                            label="Sex"
                                            required
                                            value={data.patientSex}
                                            onChange={handleChange}
                                            error={mergedErrors.patientSex}
                                            options={sexOptions}
                                        />
                                        <FloatingSelect
                                            id="patientCivilStatus"
                                            label="Civil status"
                                            value={data.patientCivilStatus}
                                            onChange={handleChange}
                                            error={mergedErrors.patientCivilStatus}
                                            options={civilStatusOptions}
                                        />
                                        <FloatingInput
                                            id="patientContactNumber"
                                            label="Contact number"
                                            type="tel"
                                            value={data.patientContactNumber}
                                            onChange={handleChange}
                                            error={mergedErrors.patientContactNumber}
                                        />
                                        <FloatingInput
                                            id="familyNumber"
                                            label="Family number"
                                            value={data.familyNumber}
                                            onChange={handleChange}
                                            error={mergedErrors.familyNumber}
                                        />
                                        <FloatingInput
                                            id="caseNumber"
                                            label="Case number"
                                            value={data.caseNumber}
                                            onChange={handleChange}
                                            error={mergedErrors.caseNumber}
                                        />
                                        <FloatingInput
                                            id="phicNumber"
                                            label="PHIC number"
                                            value={data.phicNumber}
                                            onChange={handleChange}
                                            error={mergedErrors.phicNumber}
                                        />
                                        <FloatingSelect
                                            id="religion"
                                            label="Religion"
                                            value={data.religion}
                                            onChange={handleChange}
                                            error={mergedErrors.religion}
                                            options={religionOptions}
                                        />
                                        <FloatingSelect
                                            id="bloodType"
                                            label="Blood type"
                                            value={data.bloodType}
                                            onChange={handleChange}
                                            error={mergedErrors.bloodType}
                                            options={bloodTypeOptions}
                                        />
                                        <FloatingSelect
                                            id="bloodRh"
                                            label="RH factor"
                                            value={data.bloodRh}
                                            onChange={handleChange}
                                            error={mergedErrors.bloodRh}
                                            options={bloodRhOptions}
                                        />
                                    </div>
                                </div>
                            )}

                            {step === 1 && (
                                <div className="grid gap-4">
                                    <FloatingTextarea
                                        id="patientStreetAddress"
                                        label="Street address"
                                        required
                                        value={data.patientStreetAddress}
                                        onChange={handleChange}
                                        error={mergedErrors.patientStreetAddress}
                                    />
                                    <DemographicSelector
                                        variant="vertical"
                                        value={{ region: data.region, province: data.province, city: data.city, barangay: data.barangay }}
                                        onChange={(value) => {
                                            setValue('region', value.region || '');
                                            setValue('province', value.province || '');
                                            setValue('city', value.city || '');
                                            setValue('barangay', value.barangay || '');
                                        }}
                                        canCreate
                                        errors={{
                                            region: mergedErrors.region,
                                            province: mergedErrors.province,
                                            city: mergedErrors.city,
                                            barangay: mergedErrors.barangay,
                                        }}
                                    />
                                    <div className="max-w-xs">
                                        <FloatingInput
                                            id="zipcode"
                                            label="Zip code"
                                            required
                                            value={data.zipcode}
                                            onChange={handleChange}
                                            error={mergedErrors.zipcode}
                                        />
                                    </div>
                                </div>
                            )}

                            {step === 2 && (
                                <ReferralForm
                                    hospitals={hospitals}
                                    lockGeneratedCode={isEditMode}
                                    referringFacility={data.referringFacility}
                                    referralFacility={data.referralFacility}
                                    calledDate={data.calledDate}
                                    refferalDate={data.refferalDate}
                                    transactionCode={data.transactionCode}
                                    typeOfReferral={data.typeOfReferral}
                                    referralCategory={data.referralCategory}
                                    referralReason={data.referralReason}
                                    otherReferralReason={data.otherReferralReason}
                                    contactPerson={data.contactPerson}
                                    contactDesignation={data.contactDesignation}
                                    referralContactNumber={data.referralContactNumber}
                                    referralRemarks={data.referralRemarks}
                                    errors={mergedErrors}
                                    onChange={handleReferralChange}
                                />
                            )}

                            {step === 3 && (
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="md:col-span-2">
                                        <FloatingTextarea
                                            id="diagnosis"
                                            label="Diagnosis"
                                            required
                                            value={data.diagnosis}
                                            onChange={handleChange}
                                            error={mergedErrors.diagnosis}
                                            hint="Separate multiple diagnoses with commas or new lines."
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <FloatingTextarea
                                            id="chiefComplaint"
                                            label="Chief complaint"
                                            required
                                            value={data.chiefComplaint}
                                            onChange={handleChange}
                                            error={mergedErrors.chiefComplaint}
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <FloatingTextarea
                                            id="clinicalHistory"
                                            label="History of present illness"
                                            value={data.clinicalHistory}
                                            onChange={handleChange}
                                            error={mergedErrors.clinicalHistory}
                                        />
                                    </div>
                                    <div className="md:col-span-2">
                                        <FloatingTextarea
                                            id="findings"
                                            label="Findings"
                                            value={data.findings}
                                            onChange={handleChange}
                                            error={mergedErrors.findings}
                                        />
                                    </div>
                                    <FloatingInput
                                        id="providerFirstName"
                                        label="Provider first name"
                                        required
                                        value={data.providerFirstName}
                                        onChange={handleChange}
                                        error={mergedErrors.providerFirstName}
                                    />
                                    <FloatingInput
                                        id="providerMiddleName"
                                        label="Provider middle name"
                                        value={data.providerMiddleName}
                                        onChange={handleChange}
                                        error={mergedErrors.providerMiddleName}
                                    />
                                    <FloatingInput
                                        id="providerLastName"
                                        label="Provider last name"
                                        required
                                        value={data.providerLastName}
                                        onChange={handleChange}
                                        error={mergedErrors.providerLastName}
                                    />
                                    <FloatingInput
                                        id="providerSuffix"
                                        label="Provider suffix"
                                        value={data.providerSuffix}
                                        onChange={handleChange}
                                        error={mergedErrors.providerSuffix}
                                    />
                                    <FloatingInput id="bp" label="BP" value={data.bp} onChange={handleChange} error={mergedErrors.bp} />
                                    <FloatingInput
                                        id="temp"
                                        label="Temperature"
                                        value={data.temp}
                                        onChange={handleChange}
                                        error={mergedErrors.temp}
                                    />
                                    <FloatingInput id="hr" label="Heart rate" value={data.hr} onChange={handleChange} error={mergedErrors.hr} />
                                    <FloatingInput id="rr" label="Respiratory rate" value={data.rr} onChange={handleChange} error={mergedErrors.rr} />
                                    <FloatingInput
                                        id="o2Sats"
                                        label="O2 sats"
                                        value={data.o2Sats}
                                        onChange={handleChange}
                                        error={mergedErrors.o2Sats}
                                    />
                                    <FloatingInput
                                        id="weight"
                                        label="Weight"
                                        value={data.weight}
                                        onChange={handleChange}
                                        error={mergedErrors.weight}
                                    />
                                    <FloatingInput
                                        id="height"
                                        label="Height"
                                        value={data.height}
                                        onChange={handleChange}
                                        error={mergedErrors.height}
                                    />
                                </div>
                            )}

                            {step === 4 && (
                                <div className="space-y-5">
                                    <div className="divide-y rounded-2xl border dark:border-slate-800">
                                        <ReviewRow
                                            label="Patient name"
                                            value={`${data.patientFirstName} ${data.patientMiddleName} ${data.patientLastName}`
                                                .replace(/\s+/g, ' ')
                                                .trim()}
                                        />
                                        <ReviewRow label="Birth date" value={data.patientBirthDate} />
                                        <ReviewRow label="Sex" value={data.patientSex === 'M' ? 'Male' : data.patientSex === 'F' ? 'Female' : ''} />
                                        <ReviewRow label="Religion" value={selectedReligion?.reldesc || data.religion} />
                                        <ReviewRow
                                            label="Address"
                                            value={[data.patientStreetAddress, data.barangay, data.city, data.province, data.region, data.zipcode]
                                                .filter(Boolean)
                                                .join(', ')}
                                        />
                                        <ReviewRow label={isEditMode ? 'Reference code' : 'Reference preview'} value={data.transactionCode} />
                                        <ReviewRow
                                            label="Route"
                                            value={[
                                                selectedOrigin?.facility_name || data.referringFacility,
                                                selectedDestination?.facility_name || data.referralFacility,
                                            ]
                                                .filter(Boolean)
                                                .join(' -> ')}
                                        />
                                        <ReviewRow label="Called date" value={formatDateTime(data.calledDate)} />
                                        <ReviewRow label="Referral date" value={formatDateTime(data.refferalDate)} />
                                        <ReviewRow
                                            label="Reason"
                                            value={data.referralReason === 'OTHER' ? data.otherReferralReason : data.referralReason}
                                        />
                                        <ReviewRow
                                            label="Receiving contact"
                                            value={[data.contactPerson, data.contactDesignation].filter(Boolean).join(' - ')}
                                        />
                                        <ReviewRow label="Diagnosis" value={diagnosisItems.join(', ')} />
                                        <ReviewRow label="Chief complaint" value={data.chiefComplaint} />
                                        <ReviewRow
                                            label="Referring provider"
                                            value={`${data.providerFirstName} ${data.providerMiddleName} ${data.providerLastName} ${data.providerSuffix}`
                                                .replace(/\s+/g, ' ')
                                                .trim()}
                                        />
                                    </div>
                                </div>
                            )}
                                </>
                            )}
                        </CardContent>
                        <div className="flex flex-wrap items-center gap-3 border-t px-5 py-4 md:px-6">
                            {step === 0 ? (
                                <Button asChild variant="outline">
                                    <Link href={isEditMode && id ? `/incoming/profile/${id}` : '/incoming'}>Cancel</Link>
                                </Button>
                            ) : (
                                <Button type="button" variant="outline" onClick={goBack} disabled={processing || loadingReferral}>
                                    <ChevronLeft className="size-4" />
                                    Back
                                </Button>
                            )}
                            {!(step === STEPS.length - 1) ? (
                                <Button
                                    type="button"
                                    onClick={goNext}
                                    disabled={processing || loadingReferral}
                                    className="ml-auto bg-teal-600 text-white hover:bg-teal-700"
                                >
                                    Next
                                    <ChevronRight className="size-4" />
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    onClick={submit}
                                    disabled={processing || loadingReferral}
                                    className="ml-auto bg-teal-600 text-white hover:bg-teal-700"
                                >
                                    {processing ? (
                                        <>
                                            <LoaderCircle className="size-4 animate-spin" />
                                            {isEditMode ? 'Saving changes...' : 'Submitting...'}
                                        </>
                                    ) : (
                                        <>
                                            <Save className="size-4" />
                                            {isEditMode ? 'Save changes' : 'Submit referral'}
                                        </>
                                    )}
                                </Button>
                            )}
                        </div>
                    </Card>

                    <div className="grid gap-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Progress</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {STEPS.slice(0, 4).map((item, index) => (
                                    <div key={item.label} className="flex items-center gap-3 rounded-2xl border p-3 dark:border-slate-800">
                                        <div
                                            className={cn(
                                                'flex h-8 w-8 items-center justify-center rounded-full',
                                                stepDone[index]
                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300'
                                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-900 dark:text-slate-400',
                                            )}
                                        >
                                            {stepDone[index] ? <Check className="size-4" /> : <item.icon className="size-4" />}
                                        </div>
                                        <span className="text-sm font-medium">{item.label}</span>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Live Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                                    <p className="font-medium">{`${data.patientFirstName} ${data.patientLastName}`.trim() || 'Unnamed patient'}</p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        {data.patientBirthDate || 'Birth date pending'}
                                        {data.patientSex ? ` • ${data.patientSex === 'M' ? 'Male' : 'Female'}` : ''}
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-dashed p-4 dark:border-slate-800">
                                    <p className="text-muted-foreground text-xs font-semibold tracking-[0.22em] uppercase">Route</p>
                                    <p className="mt-2 text-sm">{selectedOrigin?.facility_name || 'Origin facility pending'}</p>
                                    <p className="text-muted-foreground text-xs">to</p>
                                    <p className="text-sm">{selectedDestination?.facility_name || 'Receiving facility pending'}</p>
                                </div>
                                <div className="rounded-2xl border border-dashed p-4 dark:border-slate-800">
                                    <p className="text-muted-foreground text-xs font-semibold tracking-[0.22em] uppercase">Reference</p>
                                    <p className="mt-2 font-mono text-sm font-semibold break-all">
                                        {data.transactionCode || 'Waiting for facility selection'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
