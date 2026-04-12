import { FloatingInput, FloatingSelect, FloatingTextarea } from '@/components/ui/FloatingInput';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import axios from 'axios';
import { ClipboardList, LoaderCircle, Phone, RefreshCcw } from 'lucide-react';
import { useEffect, useState } from 'react';
import HospitalSelector, { type HospitalOption } from '../Ref_Facilities/HospitalSelector';

type ReferralFormProps = {
    hospitals: HospitalOption[];
    lockGeneratedCode?: boolean;
    referringFacility: string;
    referralFacility: string;
    calledDate: string;
    refferalDate: string;
    transactionCode: string;
    typeOfReferral: string;
    referralCategory: string;
    referralReason: string;
    otherReferralReason: string;
    contactPerson: string;
    contactDesignation: string;
    referralContactNumber: string;
    referralRemarks: string;
    errors?: Record<string, string>;
    onChange: (key: string, value: string) => void;
};

const referralTypeOptions = [
    { value: 'CONSU', label: 'Consultation' },
    { value: 'DIAGT', label: 'Diagnostic Test' },
    { value: 'TRANS', label: 'Transfer' },
    { value: 'OTHER', label: 'Others' },
];

const referralCategoryOptions = [
    { value: 'ER', label: 'Emergency' },
    { value: 'OPD', label: 'Outpatient' },
];

const referralReasonOptions = [
    { value: 'NOROM', label: 'No room available' },
    { value: 'SEASO', label: 'Seek advise/second opinion' },
    { value: 'SESPE', label: 'Seek specialized evaluation' },
    { value: 'SEFTA', label: 'Seek further treatment' },
    { value: 'NOEQP', label: 'No equipment available' },
    { value: 'NOPRO', label: 'No procedure available' },
    { value: 'NOLAB', label: 'No laboratory available' },
    { value: 'NODOC', label: 'No available doctor' },
    { value: 'OTHER', label: 'Other' },
];

export default function ReferralForm({
    hospitals,
    lockGeneratedCode = false,
    referringFacility,
    referralFacility,
    calledDate,
    refferalDate,
    transactionCode,
    typeOfReferral,
    referralCategory,
    referralReason,
    otherReferralReason,
    contactPerson,
    contactDesignation,
    referralContactNumber,
    referralRemarks,
    errors = {},
    onChange,
}: ReferralFormProps) {
    const [referringPopoverOpen, setReferringPopoverOpen] = useState(false);
    const [referralPopoverOpen, setReferralPopoverOpen] = useState(false);
    const [isGeneratingCode, setIsGeneratingCode] = useState(false);
    const [codeHint, setCodeHint] = useState('Choose the referring facility to generate a reference preview.');

    const selectedOrigin = hospitals.find((item) => item.hfhudcode === referringFacility);
    const selectedDestination = hospitals.find((item) => item.hfhudcode === referralFacility);

    useEffect(() => {
        if (lockGeneratedCode) {
            setIsGeneratingCode(false);
            setCodeHint('Reference code is locked for this saved referral.');
            return;
        }

        if (!referringFacility) {
            onChange('transactionCode', '');
            setCodeHint('Choose the referring facility to generate a reference preview.');
            return;
        }

        let cancelled = false;
        setIsGeneratingCode(true);

        axios
            .get(route('generate.hfhudcode', { hfhudcode: referringFacility }, false))
            .then((response) => {
                if (cancelled) {
                    return;
                }

                const generatedCode = response.data.code ?? response.data.hfhudcode ?? '';
                onChange('transactionCode', generatedCode);
                setCodeHint('This preview will be used when the referral is submitted, as long as it is still available.');
            })
            .catch(() => {
                if (cancelled) {
                    return;
                }

                onChange('transactionCode', '');
                setCodeHint('Unable to generate a reference preview right now.');
            })
            .finally(() => {
                if (!cancelled) {
                    setIsGeneratingCode(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [lockGeneratedCode, referringFacility, onChange]);

    const handleFieldChange = (key: string) => (event: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
        onChange(key, event.target.value);
    };

    return (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div className="md:col-span-2">
                <div className="mb-3 flex items-center gap-2">
                    <ClipboardList className="text-primary size-5" />
                    <div>
                        <h2 className="text-base font-semibold">Referral Details</h2>
                        <p className="text-muted-foreground text-sm">
                            Capture the transfer route, timing, and the receiving contact who should expect this patient.
                        </p>
                    </div>
                </div>
            </div>

            <div className="space-y-2 md:col-span-2">
                <div className="flex flex-wrap items-center gap-2">
                    <Badge variant="outline" className="rounded-full px-3 py-1">
                        Reference Preview
                    </Badge>
                    {isGeneratingCode && (
                        <span className="text-muted-foreground inline-flex items-center gap-1 text-xs">
                            <LoaderCircle className="size-3 animate-spin" />
                            Updating code
                        </span>
                    )}
                </div>

                <div
                    className={cn(
                        'rounded-2xl border border-dashed p-4',
                        transactionCode
                            ? 'border-emerald-300 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20'
                            : 'border-slate-200 bg-slate-50/80 dark:border-slate-800 dark:bg-slate-900/50',
                    )}
                >
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-muted-foreground text-xs font-medium tracking-[0.2em] uppercase">Generated code</p>
                            <p className="mt-1 font-mono text-lg font-semibold break-all text-slate-900 dark:text-slate-100">
                                {transactionCode || 'Waiting for referring facility'}
                            </p>
                        </div>
                        <RefreshCcw className={cn('text-muted-foreground size-4', isGeneratingCode && 'animate-spin')} />
                    </div>
                    <p className="text-muted-foreground mt-2 text-xs">{codeHint}</p>
                </div>
                {errors.transactionCode && <p className="text-destructive text-[11px]">{errors.transactionCode}</p>}
            </div>

            <div>
                <label className="text-muted-foreground mb-1 block px-0.5 text-[10px] font-medium tracking-[0.2em] uppercase">
                    Referring Facility *
                </label>
                <HospitalSelector
                    hospitals={hospitals}
                    selectedHospital={referringFacility}
                    setSelectedHospital={(code) => onChange('referringFacility', code)}
                    hospitalPopoverOpen={referringPopoverOpen}
                    setHospitalPopoverOpen={setReferringPopoverOpen}
                    placeholder="Select the originating facility"
                    error={errors.referringFacility}
                />
                {selectedOrigin && <p className="text-muted-foreground mt-1 text-xs">{selectedOrigin.facility_name}</p>}
            </div>

            <div>
                <label className="text-muted-foreground mb-1 block px-0.5 text-[10px] font-medium tracking-[0.2em] uppercase">
                    Receiving Facility *
                </label>
                <HospitalSelector
                    hospitals={hospitals}
                    selectedHospital={referralFacility}
                    setSelectedHospital={(code) => onChange('referralFacility', code)}
                    hospitalPopoverOpen={referralPopoverOpen}
                    setHospitalPopoverOpen={setReferralPopoverOpen}
                    placeholder="Select the receiving facility"
                    error={errors.referralFacility}
                />
                {selectedDestination && <p className="text-muted-foreground mt-1 text-xs">{selectedDestination.facility_name}</p>}
            </div>

            <FloatingInput
                id="calledDate"
                label="Datetime called"
                type="datetime-local"
                value={calledDate}
                onChange={handleFieldChange('calledDate')}
                error={errors.calledDate}
            />

            <FloatingInput
                id="refferalDate"
                label="Datetime referred"
                type="datetime-local"
                required
                value={refferalDate}
                onChange={handleFieldChange('refferalDate')}
                error={errors.refferalDate}
            />

            <FloatingSelect
                id="typeOfReferral"
                label="Type of referral"
                required
                value={typeOfReferral}
                onChange={handleFieldChange('typeOfReferral')}
                error={errors.typeOfReferral}
                options={referralTypeOptions}
            />

            <FloatingSelect
                id="referralCategory"
                label="Referral category"
                required
                value={referralCategory}
                onChange={handleFieldChange('referralCategory')}
                error={errors.referralCategory}
                options={referralCategoryOptions}
            />

            <div className="md:col-span-2">
                <FloatingSelect
                    id="referralReason"
                    label="Reason for referral"
                    required
                    value={referralReason}
                    onChange={handleFieldChange('referralReason')}
                    error={errors.referralReason}
                    options={referralReasonOptions}
                />
            </div>

            {referralReason === 'OTHER' && (
                <div className="md:col-span-2">
                    <FloatingInput
                        id="otherReferralReason"
                        label="Specify referral reason"
                        required={referralReason === 'OTHER'}
                        value={otherReferralReason}
                        onChange={handleFieldChange('otherReferralReason')}
                        error={errors.otherReferralReason}
                    />
                </div>
            )}

            <FloatingInput
                id="contactPerson"
                label="Receiving contact person"
                required
                value={contactPerson}
                onChange={handleFieldChange('contactPerson')}
                error={errors.contactPerson}
            />

            <FloatingInput
                id="contactDesignation"
                label="Designation / department"
                value={contactDesignation}
                onChange={handleFieldChange('contactDesignation')}
                error={errors.contactDesignation}
            />

            <FloatingInput
                id="referralContactNumber"
                label="Receiving contact number"
                type="tel"
                value={referralContactNumber}
                onChange={handleFieldChange('referralContactNumber')}
                error={errors.referralContactNumber}
                hint="Optional but helpful for call-backs or handoff updates."
            />

            <div className="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                <div className="flex items-start gap-2">
                    <Phone className="text-muted-foreground mt-0.5 size-4" />
                    <div>
                        <p className="text-sm font-medium">Handoff ready</p>
                        <p className="text-muted-foreground text-xs leading-5">
                            Add a contact person whenever possible so the receiving team knows who to look for during follow-up.
                        </p>
                    </div>
                </div>
            </div>

            <div className="md:col-span-2">
                <FloatingTextarea
                    id="referralRemarks"
                    label="Referral remarks"
                    value={referralRemarks}
                    onChange={handleFieldChange('referralRemarks')}
                    error={errors.referralRemarks}
                    hint="Use this for urgency context, transport details, or special instructions."
                />
            </div>
        </div>
    );
}
