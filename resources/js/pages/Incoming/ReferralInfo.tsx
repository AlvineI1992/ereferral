import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Ambulance, ArrowRight, Building2, CalendarDays, ClipboardList, Hash } from 'lucide-react';

type Referral = {
    LogID: string;
    date: string;
    reason: string;
    type: string;
    category: string;
};

type ReferralFacility = {
    facility_name: string;
    hfhudcode: string;
};

type Props = {
    referral: Referral | null;
    referral_origin: ReferralFacility | null;
    referral_dest: ReferralFacility | null;
};

export default function ReferralInfo({ referral, referral_origin, referral_dest }: Props) {
    const categoryTone =
        referral?.category === 'Emergency'
            ? 'rounded-full border border-rose-200 bg-rose-50 text-rose-700'
            : 'rounded-full border border-emerald-200 bg-emerald-50 text-emerald-700';

    return (
        <Card className="border-slate-200/80 bg-white/90 shadow-sm">
            <CardHeader className="border-b border-slate-100 pb-5">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                        <Ambulance className="size-5" />
                    </div>
                    <div>
                        <CardTitle className="text-lg">Referral Information</CardTitle>
                        <p className="mt-1 text-sm text-slate-500">Routing, urgency, and handoff context for the receiving team.</p>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-5 pt-6">
                <div className="flex flex-wrap items-center gap-2">
                    {referral?.LogID && (
                        <Badge variant="outline" className="rounded-full border-slate-200 bg-slate-50">
                            <Hash className="size-3" />
                            {referral.LogID}
                        </Badge>
                    )}
                    {referral?.date && (
                        <Badge variant="outline" className="rounded-full border-slate-200 bg-slate-50">
                            <CalendarDays className="size-3" />
                            {referral.date}
                        </Badge>
                    )}
                    {referral?.category && <span className={categoryTone}>{referral.category}</span>}
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                        <div className="flex items-center gap-2">
                            <ClipboardList className="size-4 text-amber-700" />
                            <p className="text-sm font-semibold text-slate-900">Referral Summary</p>
                        </div>

                        <div className="mt-3 space-y-2 text-sm text-slate-600">
                            <p><span className="font-medium text-slate-900">Type:</span> {referral?.type || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">Reason:</span> {referral?.reason || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">Category:</span> {referral?.category || 'N/A'}</p>
                        </div>
                    </div>

                    <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                        <div className="flex items-center gap-2">
                            <Building2 className="size-4 text-cyan-700" />
                            <p className="text-sm font-semibold text-slate-900">Facility Route</p>
                        </div>

                        <div className="mt-3 space-y-3 text-sm text-slate-600">
                            <div>
                                <p className="font-medium text-slate-900">Origin</p>
                                <p>{referral_origin?.facility_name || 'N/A'}</p>
                                <p className="text-xs text-slate-500">{referral_origin?.hfhudcode || 'No facility code'}</p>
                            </div>

                            <div className="flex items-center gap-2 text-slate-400">
                                <ArrowRight className="size-4" />
                                <span className="text-xs uppercase tracking-[0.2em]">To</span>
                            </div>

                            <div>
                                <p className="font-medium text-slate-900">Destination</p>
                                <p>{referral_dest?.facility_name || 'N/A'}</p>
                                <p className="text-xs text-slate-500">{referral_dest?.hfhudcode || 'No facility code'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
