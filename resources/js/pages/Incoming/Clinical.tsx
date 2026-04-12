import { useEffect, useState } from 'react';
import axios from 'axios';
import { Activity, Heart, Ruler, Scale, Thermometer, Wind } from 'lucide-react';

type Vitals = {
    BP?: string;
    Temp?: string;
    HR?: string;
    RR?: string;
    O2Sats?: string;
    Weight?: string;
    Height?: string;
};

type ReferralClinicalData = {
    LogID: string;
    diagnosis: string;
    history: string;
    physical_examination: string;
    chief_complaint: string;
    findings: string;
    vitals: Vitals | null;
};

type ClinicalInfoProps = {
    logID: string;
};

const vitalCards = [
    { key: 'BP', label: 'BP', icon: Heart },
    { key: 'Temp', label: 'Temp', icon: Thermometer },
    { key: 'HR', label: 'HR', icon: Activity },
    { key: 'RR', label: 'RR', icon: Wind },
    { key: 'O2Sats', label: 'O2 Sats', icon: Activity },
    { key: 'Weight', label: 'Weight', icon: Scale },
    { key: 'Height', label: 'Height', icon: Ruler },
] as const;

export default function ClinicalInfo({ logID }: ClinicalInfoProps) {
    const [clinicalData, setClinicalData] = useState<ReferralClinicalData | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!logID) {
            return;
        }

        const fetchClinicalData = async () => {
            try {
                setLoading(true);
                const response = await axios.get(`/referral-clinical/${logID}`);
                setClinicalData(response.data);
            } catch (error) {
                console.error('Failed to fetch clinical data:', error);
            } finally {
                setLoading(false);
            }
        };

        void fetchClinicalData();
    }, [logID]);

    if (loading) {
        return <div className="text-sm text-slate-500">Loading clinical data...</div>;
    }

    if (!clinicalData) {
        return <div className="text-sm text-slate-500">No clinical data found.</div>;
    }

    const vitals = clinicalData.vitals ?? {};

    return (
        <div className="space-y-5">
            <div className="grid gap-4 md:grid-cols-2">
                <ClinicalBlock title="Chief complaint" value={clinicalData.chief_complaint} />
                <ClinicalBlock title="Diagnosis" value={clinicalData.diagnosis} />
                <ClinicalBlock title="History" value={clinicalData.history} />
                <ClinicalBlock title="Physical examination" value={clinicalData.physical_examination} />
            </div>

            <ClinicalBlock title="Findings" value={clinicalData.findings} />

            <div>
                <h3 className="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Vital signs</h3>
                <div className="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {vitalCards.map((item) => {
                        const Icon = item.icon;
                        const value = vitals[item.key];

                        return (
                            <div key={item.key} className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                                <div className="flex items-center justify-between gap-3">
                                    <p className="text-xs font-medium uppercase tracking-[0.2em] text-slate-500">{item.label}</p>
                                    <div className="rounded-xl bg-white p-2 text-teal-700 shadow-sm">
                                        <Icon className="size-4" />
                                    </div>
                                </div>
                                <p className="mt-3 text-lg font-semibold text-slate-900">{value || '-'}</p>
                            </div>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function ClinicalBlock({ title, value }: { title: string; value?: string }) {
    return (
        <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
            <p className="text-sm font-semibold text-slate-900">{title}</p>
            <p className="mt-2 text-sm leading-6 text-slate-600">{value || '-'}</p>
        </div>
    );
}
