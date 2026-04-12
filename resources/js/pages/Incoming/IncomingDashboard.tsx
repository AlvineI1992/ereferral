import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { Activity, ArrowUpRight, Building2, CircleAlert, Clock3, MapPinned, MapPlus, Plus, type LucideIcon } from 'lucide-react';

import type { IncomingGeoItem, IncomingSummary } from './types';

type IncomingDashboardProps = {
    summary: IncomingSummary;
    canCreate: boolean;
};

const metrics: Array<{
    key: keyof Pick<IncomingSummary, 'totalIncoming' | 'todayIncoming' | 'emergencyCount' | 'outpatientCount' | 'receivingFacilities'>;
    label: string;
    detail: string;
    icon: LucideIcon;
    tone: string;
}> = [
    {
        key: 'totalIncoming',
        label: 'Open incoming',
        detail: 'Current queue without tracking activity yet.',
        icon: Clock3,
        tone: 'border-amber-200/80 bg-amber-50/80 text-amber-900',
    },
    {
        key: 'todayIncoming',
        label: 'Added today',
        detail: 'Fresh referrals logged in today.',
        icon: Activity,
        tone: 'border-cyan-200/80 bg-cyan-50/80 text-cyan-900',
    },
    {
        key: 'emergencyCount',
        label: 'Emergency',
        detail: 'Incoming emergency referrals needing close monitoring.',
        icon: CircleAlert,
        tone: 'border-rose-200/80 bg-rose-50/80 text-rose-900',
    },
    {
        key: 'outpatientCount',
        label: 'Outpatient',
        detail: 'Queued outpatient referrals in the same scope.',
        icon: Activity,
        tone: 'border-emerald-200/80 bg-emerald-50/80 text-emerald-900',
    },
    {
        key: 'receivingFacilities',
        label: 'Receiving facilities',
        detail: 'Facilities currently represented in this queue.',
        icon: Building2,
        tone: 'border-slate-200/80 bg-slate-50/90 text-slate-900',
    },
];

function formatNumber(value: number) {
    return new Intl.NumberFormat('en-US').format(value);
}

function formatGeneratedAt(value: string) {
    try {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
        }).format(new Date(value));
    } catch {
        return value;
    }
}

export default function IncomingDashboard({ summary, canCreate }: IncomingDashboardProps) {
    const locationPanels: Array<{
        key: 'topProvinces' | 'topCities' | 'topBarangays';
        title: string;
        caption: string;
        icon: LucideIcon;
    }> = [
        {
            key: 'topProvinces',
            title: 'By province',
            caption: 'Highest patient origin volume',
            icon: MapPinned,
        },
        {
            key: 'topCities',
            title: 'By city',
            caption: 'Most active LGU sources',
            icon: Building2,
        },
        {
            key: 'topBarangays',
            title: 'By barangay',
            caption: 'Frontline community hotspots',
            icon: MapPlus,
        },
    ];

    return (
        <div className="space-y-3">
            <section className="relative overflow-hidden rounded-[1.75rem] border border-slate-200/80 bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-800 p-4 text-white shadow-lg shadow-teal-950/10">
                <div className="absolute top-0 -left-10 h-32 w-32 rounded-full bg-cyan-300/10 blur-3xl" />
                <div className="absolute right-0 bottom-0 h-36 w-36 rounded-full bg-emerald-200/10 blur-3xl" />
                <div className="relative flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div className="space-y-2">
                        <Badge className="w-fit border-white/15 bg-white/12 px-2.5 py-1 text-[11px] text-white backdrop-blur-sm">Incoming Command Center</Badge>
                        <div className="space-y-1.5">
                            <h1 className="text-xl font-semibold tracking-tight sm:text-2xl">
                                Queue pressure, geography, and referral drivers in one compact view.
                            </h1>
                            <p className="max-w-3xl text-sm leading-6 text-teal-50/80">
                                A balanced operations snapshot for incoming referrals, tuned for quick triage and area-based monitoring.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2.5">
                        {canCreate && (
                            <Button asChild className="h-10 rounded-full bg-white text-teal-950 hover:bg-teal-50">
                                <Link href="/referrals/create">
                                    <Plus className="size-4" />
                                    Add referral
                                </Link>
                            </Button>
                        )}
                        <div className="rounded-full border border-white/15 bg-white/10 px-3.5 py-2 text-sm text-teal-50/85 backdrop-blur-sm">
                            Updated {formatGeneratedAt(summary.generatedAt)}
                        </div>
                    </div>
                </div>
            </section>

            <section className="grid gap-3 md:grid-cols-2 md:grid-cols-3">
                <div className="grid gap-3 md:col-span-2 md:col-span-3 md:grid-cols-5">
                    {metrics.map((metric) => {
                        const Icon = metric.icon;

                        return (
                            <Card key={metric.key} className={`border shadow-sm ${metric.tone}`}>
                                <CardContent className="p-3.5">
                                    <div className="flex items-start justify-between gap-2">
                                        <div>
                                            <p className="text-xs font-medium uppercase tracking-[0.18em]">{metric.label}</p>
                                            <p className="mt-2 text-2xl font-semibold tracking-tight">{formatNumber(summary[metric.key])}</p>
                                        </div>
                                        <div className="rounded-l bg-white/70 p-2">
                                            <Icon className="size-4" />
                                        </div>
                                    </div>
                                    <p className="mt-3 text-xs leading-5 text-slate-600">{metric.detail}</p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <Card className="border-slate-200/80 bg-white/90 shadow-sm md:col-span-2 xl:col-span-6">
                    <CardContent className="p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm font-semibold text-slate-900">Top referral reasons</p>
                                <p className="mt-1 text-xs leading-5 text-slate-500">Most common incoming drivers in the active queue.</p>
                            </div>
                            <ArrowUpRight className="size-4 text-slate-400" />
                        </div>

                        <div className="mt-4 grid gap-2">
                            {summary.topReasons.length > 0 ? (
                                summary.topReasons.map((reason) => (
                                    <div key={`${reason.code}-${reason.label}`} className="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-3">
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-slate-900">{reason.label}</p>
                                               
                                            </div>
                                            <Badge variant="outline" className="rounded-full px-2.5 py-1">
                                                {formatNumber(reason.count)}
                                            </Badge>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500">
                                    Referral reasons will appear here once incoming cases are available.
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section className="grid gap-3 xl:grid-cols-3">
                {locationPanels.map((panel) => (
                    <Card key={panel.key} className="border-slate-200/80 bg-white/90 shadow-sm">
                        <CardContent className="p-4">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-sm font-semibold text-slate-900">{panel.title}</p>
                                    <p className="mt-1 text-xs leading-5 text-slate-500">{panel.caption}</p>
                                </div>
                                <div className="rounded-xl bg-slate-100 p-2 text-slate-700">
                                    <panel.icon className="size-4" />
                                </div>
                            </div>

                            <div className="mt-4 space-y-2">
                                {summary[panel.key].length > 0 ? (
                                    summary[panel.key].map((item) => <LocationRow key={`${panel.key}-${item.code}-${item.label}`} item={item} />)
                                ) : (
                                    <div className="rounded-2xl border border-dashed border-slate-200 p-4 text-xs text-slate-500">
                                        Geographic activity will appear here once incoming cases are available.
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </section>
        </div>
    );
}

function LocationRow({ item }: { item: IncomingGeoItem }) {
    return (
        <div className="flex items-center justify-between gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 px-3 py-2.5">
            <div className="min-w-0">
                <p className="truncate text-sm font-medium text-slate-900">{item.label}</p>

            </div>
            <Badge variant="outline" className="rounded-full px-2.5 py-1">
                {formatNumber(item.count)}
            </Badge>
        </div>
    );
}
