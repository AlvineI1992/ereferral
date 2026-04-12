import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    ArrowUpRight,
    Building2,
    CircleAlert,
    Clock3,
    Inbox,
    type LucideIcon,
    Plus,
    Route,
    Stethoscope,
    Users,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

type SummaryCard = {
    key: string;
    label: string;
    value: number;
    detail: string;
};

type ActivityPoint = {
    label: string;
    date: string;
    count: number;
};

type RecentReferral = {
    id: string;
    patientName: string;
    originName: string;
    destinationName: string;
    scheduledFor: string;
    category: string;
    reason: string;
    type: string;
    status: string;
    statusTone: 'tracked' | 'queue' | string;
};

type ReasonItem = {
    code: string;
    label: string;
    count: number;
    share: number;
};

type NetworkStat = {
    key: string;
    label: string;
    value: number;
    detail: string;
};

type QuickAction = {
    key: string;
    title: string;
    description: string;
    href: string;
};

type Scope = {
    label: string;
    description: string;
    totalReferrals: number;
    trackedReferrals: number;
};

type CapacityItem = {
    key: string;
    label: string;
    totalBeds: number;
    occupiedBeds: number;
    reservedBeds: number;
    availableBeds: number;
    occupancyRate: number;
};

type BedSummary = {
    totals: {
        totalBeds: number;
        occupiedBeds: number;
        reservedBeds: number;
        availableBeds: number;
        occupancyRate: number;
        facilitiesReporting: number;
        bedTypesTracked: number;
    };
    byFacility: CapacityItem[];
    byRegion: CapacityItem[];
};

type DashboardProps = {
    summary: SummaryCard[];
    activity: ActivityPoint[];
    recentReferrals: RecentReferral[];
    topReasons: ReasonItem[];
    network: NetworkStat[];
    bedSummary: BedSummary;
    quickActions: QuickAction[];
    scope: Scope;
    generatedAt: string;
};

const metricIcons: Record<string, LucideIcon> = {
    today: Activity,
    queue: Clock3,
    emergency: CircleAlert,
    tracked: Route,
};

const metricStyles: Record<string, string> = {
    today: 'border-cyan-200/70 bg-cyan-50/70 dark:border-cyan-900/70 dark:bg-cyan-950/30',
    queue: 'border-amber-200/80 bg-amber-50/80 dark:border-amber-900/70 dark:bg-amber-950/30',
    emergency: 'border-rose-200/80 bg-rose-50/80 dark:border-rose-900/70 dark:bg-rose-950/30',
    tracked: 'border-emerald-200/80 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/30',
};

const iconShellStyles: Record<string, string> = {
    today: 'bg-cyan-500/15 text-cyan-700 dark:bg-cyan-400/15 dark:text-cyan-300',
    queue: 'bg-amber-500/15 text-amber-700 dark:bg-amber-400/15 dark:text-amber-300',
    emergency: 'bg-rose-500/15 text-rose-700 dark:bg-rose-400/15 dark:text-rose-300',
    tracked: 'bg-emerald-500/15 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300',
};

const networkIcons: Record<string, LucideIcon> = {
    facilities: Building2,
    providers: Stethoscope,
    users: Users,
};

const actionIcons: Record<string, LucideIcon> = {
    'new-referral': Plus,
    incoming: Inbox,
    patients: Users,
    facilities: Building2,
};

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

export default function Dashboard({ summary, activity, recentReferrals, topReasons, network, bedSummary, quickActions, scope, generatedAt }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const firstName = auth.user?.name?.split(' ')[0] ?? 'Team';
    const peakActivity = Math.max(...activity.map((point) => point.count), 1);
    const trackedRate = scope.totalReferrals > 0 ? Math.round((scope.trackedReferrals / scope.totalReferrals) * 100) : 0;
    const focusMetrics = summary.filter((metric) => ['queue', 'emergency', 'tracked'].includes(metric.key));
    const bedTotals = bedSummary.totals;
    const capacityHighlights = [
        {
            key: 'available',
            label: 'Available beds',
            value: bedTotals.availableBeds,
            detail: 'Beds open for immediate placement across active trackers.',
            tone: 'border-emerald-200/80 bg-emerald-50/80 dark:border-emerald-900/70 dark:bg-emerald-950/30',
        },
        {
            key: 'occupied',
            label: 'Occupied beds',
            value: bedTotals.occupiedBeds,
            detail: `${bedTotals.occupancyRate}% current occupancy across reported capacity.`,
            tone: 'border-cyan-200/80 bg-cyan-50/80 dark:border-cyan-900/70 dark:bg-cyan-950/30',
        },
        {
            key: 'reporting',
            label: 'Facilities reporting',
            value: bedTotals.facilitiesReporting,
            detail: 'Facilities with at least one active bed tracker entry.',
            tone: 'border-amber-200/80 bg-amber-50/80 dark:border-amber-900/70 dark:bg-amber-950/30',
        },
        {
            key: 'tracked-types',
            label: 'Bed types tracked',
            value: bedTotals.bedTypesTracked,
            detail: `${formatNumber(bedTotals.reservedBeds)} beds currently reserved for handoff planning.`,
            tone: 'border-violet-200/80 bg-violet-50/80 dark:border-violet-900/70 dark:bg-violet-950/30',
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-[2rem] border border-slate-200/70 bg-gradient-to-br from-teal-950 via-teal-900 to-emerald-800 text-white shadow-xl shadow-teal-950/10">
                    <div className="absolute top-0 -left-20 h-48 w-48 rounded-full bg-cyan-300/10 blur-3xl" />
                    <div className="absolute right-0 bottom-0 h-56 w-56 rounded-full bg-emerald-200/10 blur-3xl" />
                    <div className="relative grid gap-6 p-6 lg:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.95fr)] lg:p-8">
                        <div className="space-y-5">
                            <Badge className="border-white/15 bg-white/12 px-3 py-1 text-white backdrop-blur-sm">{scope.label}</Badge>
                            <div className="space-y-3">
                                <p className="text-sm text-teal-100/80">Hello, {firstName}. Here's the latest referral pulse.</p>
                                <h1 className="max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">
                                    A clearer view of demand, queue pressure, and follow-through across the eReferral network.
                                </h1>
                                <p className="max-w-2xl text-sm leading-6 text-teal-50/80 sm:text-base">{scope.description}</p>
                            </div>

                            <div className="flex flex-wrap gap-3">
                                {quickActions.slice(0, 2).map((action) => {
                                    const ActionIcon = actionIcons[action.key] ?? ArrowUpRight;

                                    return (
                                        <Button
                                            key={action.key}
                                            asChild
                                            className="h-11 rounded-full border border-white/15 bg-white text-teal-950 shadow-sm hover:bg-teal-50"
                                        >
                                            <Link href={action.href}>
                                                <ActionIcon className="size-4" />
                                                {action.title}
                                            </Link>
                                        </Button>
                                    );
                                })}
                                <Button
                                    asChild
                                    variant="outline"
                                    className="h-11 rounded-full border-white/20 bg-white/8 text-white hover:bg-white/12 hover:text-white"
                                >
                                    <Link href="/facilities">
                                        <Building2 className="size-4" />
                                        View network
                                    </Link>
                                </Button>
                            </div>

                            <div className="flex flex-wrap gap-3 text-sm text-teal-50/85">
                                <div className="rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-sm">
                                    {formatNumber(scope.totalReferrals)} referrals in this view
                                </div>
                                <div className="rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-sm">
                                    {trackedRate}% already have tracking activity
                                </div>
                                <div className="rounded-full border border-white/15 bg-white/10 px-4 py-2 backdrop-blur-sm">
                                    Updated {formatGeneratedAt(generatedAt)}
                                </div>
                            </div>
                        </div>

                        <div className="rounded-[1.75rem] border border-white/12 bg-white/10 p-5 backdrop-blur-md">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium tracking-[0.28em] text-teal-100/70 uppercase">Operational Focus</p>
                                    <p className="mt-2 text-sm text-teal-50/80">Priority signals your team may want to act on first.</p>
                                </div>
                                <div className="rounded-2xl bg-slate-950/15 p-3 text-right">
                                    <p className="text-xs tracking-[0.2em] text-teal-100/60 uppercase">Coverage</p>
                                    <p className="text-2xl font-semibold">{trackedRate}%</p>
                                </div>
                            </div>

                            <div className="mt-5 space-y-3">
                                {focusMetrics.map((metric) => {
                                    const Icon = metricIcons[metric.key] ?? Activity;

                                    return (
                                        <div
                                            key={metric.key}
                                            className="flex items-center justify-between rounded-2xl border border-white/10 bg-slate-950/10 px-4 py-3"
                                        >
                                            <div className="flex items-center gap-3">
                                                <div className="rounded-2xl bg-white/10 p-2">
                                                    <Icon className="size-4 text-white" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium text-white">{metric.label}</p>
                                                    <p className="text-xs text-teal-100/70">{metric.detail}</p>
                                                </div>
                                            </div>
                                            <p className="text-2xl font-semibold">{formatNumber(metric.value)}</p>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {summary.map((metric) => {
                        const Icon = metricIcons[metric.key] ?? Activity;

                        return (
                            <Card key={metric.key} className={cn('overflow-hidden border shadow-sm', metricStyles[metric.key])}>
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <CardDescription className="text-sm text-slate-600 dark:text-slate-300">{metric.label}</CardDescription>
                                            <CardTitle className="mt-3 text-3xl font-semibold tracking-tight">{formatNumber(metric.value)}</CardTitle>
                                        </div>
                                        <div className={cn('rounded-2xl p-2.5', iconShellStyles[metric.key])}>
                                            <Icon className="size-5" />
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm leading-6 text-slate-600 dark:text-slate-300">{metric.detail}</p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(340px,0.9fr)]">
                    <Card className="overflow-hidden border-slate-200/80 bg-white/95 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/85">
                        <CardHeader className="flex flex-col gap-4 border-b border-slate-200/70 pb-5 dark:border-slate-800">
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <CardTitle>Bed Capacity Snapshot</CardTitle>
                                    <CardDescription>
                                        Live availability rolled up from active bed tracker entries inside your current dashboard scope.
                                    </CardDescription>
                                </div>
                                <Button asChild variant="outline" className="rounded-full">
                                    <Link href="/bed_tracker">
                                        Open bed tracker
                                        <ArrowUpRight className="size-4" />
                                    </Link>
                                </Button>
                            </div>

                            <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                {capacityHighlights.map((item) => (
                                    <div key={item.key} className={cn('rounded-[1.25rem] border p-4', item.tone)}>
                                        <p className="text-xs font-medium tracking-[0.2em] text-slate-600 uppercase dark:text-slate-300">
                                            {item.label}
                                        </p>
                                        <p className="mt-3 text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
                                            {formatNumber(item.value)}
                                        </p>
                                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{item.detail}</p>
                                    </div>
                                ))}
                            </div>
                        </CardHeader>
                        <CardContent className="grid gap-4 pt-6 lg:grid-cols-2">
                            <div className="rounded-[1.5rem] border border-slate-200/80 bg-emerald-50/50 p-5 dark:border-slate-800 dark:bg-emerald-950/10">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">Availability Mix</p>
                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            Compare open, occupied, and reserved capacity from the same reporting pool.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl bg-white/80 px-3 py-2 text-right shadow-sm dark:bg-slate-900/80">
                                        <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">Total beds</p>
                                        <p className="text-2xl font-semibold text-slate-900 dark:text-slate-100">{formatNumber(bedTotals.totalBeds)}</p>
                                    </div>
                                </div>

                                <div className="mt-5 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                    <div className="flex h-full w-full">
                                        <div
                                            className="bg-emerald-500"
                                            style={{ width: `${bedTotals.totalBeds > 0 ? (bedTotals.availableBeds / bedTotals.totalBeds) * 100 : 0}%` }}
                                        />
                                        <div
                                            className="bg-cyan-500"
                                            style={{ width: `${bedTotals.totalBeds > 0 ? (bedTotals.occupiedBeds / bedTotals.totalBeds) * 100 : 0}%` }}
                                        />
                                        <div
                                            className="bg-amber-400"
                                            style={{ width: `${bedTotals.totalBeds > 0 ? (bedTotals.reservedBeds / bedTotals.totalBeds) * 100 : 0}%` }}
                                        />
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3 sm:grid-cols-3">
                                    <div className="rounded-2xl border border-emerald-200/80 bg-white/80 p-4 dark:border-emerald-900/70 dark:bg-slate-900/80">
                                        <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">Available</p>
                                        <p className="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-300">
                                            {formatNumber(bedTotals.availableBeds)}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-cyan-200/80 bg-white/80 p-4 dark:border-cyan-900/70 dark:bg-slate-900/80">
                                        <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">Occupied</p>
                                        <p className="mt-2 text-2xl font-semibold text-cyan-700 dark:text-cyan-300">
                                            {formatNumber(bedTotals.occupiedBeds)}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-amber-200/80 bg-white/80 p-4 dark:border-amber-900/70 dark:bg-slate-900/80">
                                        <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">Reserved</p>
                                        <p className="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300">
                                            {formatNumber(bedTotals.reservedBeds)}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/80 p-5 dark:border-slate-800 dark:bg-slate-900/60">
                                <div className="flex items-start justify-between gap-3">
                                    <div>
                                        <p className="text-sm font-semibold text-slate-900 dark:text-slate-100">Regional Capacity</p>
                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                            Highest available capacity by region within your access scope.
                                        </p>
                                    </div>
                                    <Badge variant="outline" className="rounded-full px-3 py-1">
                                        {bedSummary.byRegion.length} regions
                                    </Badge>
                                </div>

                                <div className="mt-5 space-y-3">
                                    {bedSummary.byRegion.length > 0 ? (
                                        bedSummary.byRegion.map((region) => (
                                            <div key={region.key} className="rounded-2xl border border-slate-200/80 bg-white/90 p-4 dark:border-slate-800 dark:bg-slate-950/70">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="truncate font-medium text-slate-900 dark:text-slate-100">{region.label}</p>
                                                        <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                            {formatNumber(region.availableBeds)} available of {formatNumber(region.totalBeds)} total beds
                                                        </p>
                                                    </div>
                                                    <Badge className="rounded-full bg-emerald-500/12 px-3 py-1 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300">
                                                        {region.occupancyRate}% occupied
                                                    </Badge>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <div className="rounded-2xl border border-dashed border-slate-200 p-5 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                            Regional totals will appear once facilities in this scope start reporting active bed entries.
                                        </div>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                        <CardHeader>
                            <CardTitle>Available Beds by Facility</CardTitle>
                            <CardDescription>Facilities with the highest immediate bed availability from active tracker records.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {bedSummary.byFacility.length > 0 ? (
                                bedSummary.byFacility.map((facility) => (
                                    <div key={facility.key} className="rounded-[1.5rem] border border-slate-200/80 p-4 dark:border-slate-800">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate font-medium text-slate-900 dark:text-slate-100">{facility.label}</p>
                                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                                    {formatNumber(facility.availableBeds)} open of {formatNumber(facility.totalBeds)} total beds
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-2xl font-semibold tracking-tight text-emerald-700 dark:text-emerald-300">
                                                    {formatNumber(facility.availableBeds)}
                                                </p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400">{facility.occupancyRate}% occupied</p>
                                            </div>
                                        </div>
                                        <div className="mt-4 h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div
                                                className="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                                style={{
                                                    width: `${facility.totalBeds > 0 && facility.availableBeds > 0 ? Math.max((facility.availableBeds / facility.totalBeds) * 100, 4) : 0}%`,
                                                }}
                                            />
                                        </div>
                                        <div className="mt-3 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                            <span>Occupied {formatNumber(facility.occupiedBeds)}</span>
                                            <span>Reserved {formatNumber(facility.reservedBeds)}</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-[1.5rem] border border-dashed border-slate-200 p-8 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                    No active bed tracker data is available yet for this dashboard scope.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
                    <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <div>
                                <CardTitle>7-Day Activity</CardTitle>
                                <CardDescription>Referral submissions captured over the last seven days.</CardDescription>
                            </div>
                            <div className="rounded-full bg-cyan-500/10 px-3 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-400/10 dark:text-cyan-300">
                                Rolling window
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="flex min-h-[260px] items-end gap-3 rounded-[1.5rem] border border-dashed border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                                {activity.map((point) => {
                                    const height = point.count === 0 ? 10 : Math.max(Math.round((point.count / peakActivity) * 100), 16);

                                    return (
                                        <div key={point.date} className="flex flex-1 flex-col items-center gap-3">
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-200">
                                                {formatNumber(point.count)}
                                            </span>
                                            <div className="relative flex h-40 w-full items-end rounded-full bg-slate-200/90 p-1 dark:bg-slate-800">
                                                <div
                                                    className="w-full rounded-full bg-gradient-to-t from-teal-600 via-cyan-500 to-emerald-400 shadow-sm"
                                                    style={{ height: `${height}%` }}
                                                />
                                            </div>
                                            <div className="text-center">
                                                <p className="text-sm font-medium text-slate-800 dark:text-slate-100">{point.label}</p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400">{point.date}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                        <CardHeader>
                            <CardTitle>Referral Drivers</CardTitle>
                            <CardDescription>The most common reasons showing up in your current dashboard scope.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {topReasons.length > 0 ? (
                                topReasons.map((reason) => (
                                    <div key={reason.code} className="space-y-2 rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium text-slate-900 dark:text-slate-100">{reason.label}</p>
                                                <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">{reason.code}</p>
                                            </div>
                                            <Badge variant="outline" className="rounded-full px-2.5 py-1">
                                                {formatNumber(reason.count)}
                                            </Badge>
                                        </div>
                                        <div className="h-2 rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div
                                                className="h-2 rounded-full bg-gradient-to-r from-teal-600 to-cyan-500"
                                                style={{ width: `${Math.max(reason.share, 6)}%` }}
                                            />
                                        </div>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                            {reason.share}% of the referrals represented in this top list.
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-2xl border border-dashed border-slate-200 p-6 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                    Referral reasons will appear here once new cases are recorded.
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </section>

                <section className="grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(320px,0.9fr)]">
                    <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                        <CardHeader className="flex flex-row items-start justify-between space-y-0">
                            <div>
                                <CardTitle>Recent Referrals</CardTitle>
                                <CardDescription>The newest cases currently visible from your assigned access scope.</CardDescription>
                            </div>
                            <Button asChild variant="outline" className="rounded-full">
                                <Link href="/incoming">
                                    Open queue
                                    <ArrowUpRight className="size-4" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {recentReferrals.length > 0 ? (
                                recentReferrals.map((referral) => (
                                    <div
                                        key={referral.id}
                                        className="grid gap-4 rounded-[1.5rem] border border-slate-200/80 p-4 lg:grid-cols-[minmax(0,1.15fr)_minmax(220px,0.95fr)_auto] dark:border-slate-800"
                                    >
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <p className="text-base font-semibold text-slate-900 dark:text-slate-100">{referral.patientName}</p>
                                                <Badge variant="outline" className="rounded-full text-[11px]">
                                                    {referral.category}
                                                </Badge>
                                            </div>
                                            <p className="text-sm text-slate-600 dark:text-slate-300">{referral.reason}</p>
                                            <p className="text-xs tracking-[0.2em] text-slate-500 uppercase dark:text-slate-400">{referral.id}</p>
                                        </div>

                                        <div className="space-y-2">
                                            <div className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-200">
                                                <span className="truncate">{referral.originName}</span>
                                                <ArrowRight className="size-4 shrink-0 text-slate-400" />
                                                <span className="truncate">{referral.destinationName}</span>
                                            </div>
                                            <p className="text-sm text-slate-600 dark:text-slate-300">{referral.type}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">{referral.scheduledFor}</p>
                                        </div>

                                        <div className="flex flex-col items-start gap-2 lg:items-end">
                                            <Badge
                                                className={cn(
                                                    'rounded-full px-3 py-1',
                                                    referral.statusTone === 'tracked'
                                                        ? 'bg-emerald-500/12 text-emerald-700 dark:bg-emerald-400/12 dark:text-emerald-300'
                                                        : 'bg-amber-500/12 text-amber-700 dark:bg-amber-400/12 dark:text-amber-300',
                                                )}
                                            >
                                                {referral.status}
                                            </Badge>
                                            <span className="text-xs text-slate-500 dark:text-slate-400">Needs attention from care coordination</span>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="rounded-[1.5rem] border border-dashed border-slate-200 p-8 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                    No referrals have been logged yet for this dashboard view.
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <div className="grid gap-4">
                        <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                            <CardHeader>
                                <CardTitle>Network Snapshot</CardTitle>
                                <CardDescription>A quick read on the teams and facilities supporting the workflow.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {network.map((item) => {
                                    const Icon = networkIcons[item.key] ?? Building2;

                                    return (
                                        <div
                                            key={item.key}
                                            className="flex items-start gap-3 rounded-2xl border border-slate-200/70 p-4 dark:border-slate-800"
                                        >
                                            <div className="rounded-2xl bg-slate-100 p-2.5 text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                                <Icon className="size-5" />
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <div className="flex items-baseline justify-between gap-3">
                                                    <p className="font-medium text-slate-900 dark:text-slate-100">{item.label}</p>
                                                    <p className="text-2xl font-semibold tracking-tight">{formatNumber(item.value)}</p>
                                                </div>
                                                <p className="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{item.detail}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </CardContent>
                        </Card>

                        <Card className="border-slate-200/80 bg-white/90 shadow-sm dark:border-slate-800/80 dark:bg-slate-950/80">
                            <CardHeader>
                                <CardTitle>Quick Actions</CardTitle>
                                <CardDescription>Jump straight into the workflows teams usually need next.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {quickActions.map((action) => {
                                    const ActionIcon = actionIcons[action.key] ?? ArrowUpRight;

                                    return (
                                        <Link
                                            key={action.key}
                                            href={action.href}
                                            className="group flex items-center justify-between rounded-2xl border border-slate-200/70 p-4 transition hover:border-teal-300 hover:bg-teal-50/60 dark:border-slate-800 dark:hover:border-teal-900 dark:hover:bg-teal-950/20"
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="rounded-2xl bg-teal-500/10 p-2.5 text-teal-700 dark:bg-teal-400/10 dark:text-teal-300">
                                                    <ActionIcon className="size-5" />
                                                </div>
                                                <div>
                                                    <p className="font-medium text-slate-900 dark:text-slate-100">{action.title}</p>
                                                    <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">{action.description}</p>
                                                </div>
                                            </div>
                                            <ArrowUpRight className="size-4 text-slate-400 transition group-hover:text-teal-600 dark:group-hover:text-teal-300" />
                                        </Link>
                                    );
                                })}
                            </CardContent>
                        </Card>
                    </div>
                </section>
            </div>
        </AppLayout>
    );
}

