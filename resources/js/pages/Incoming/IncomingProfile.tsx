import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
    Activity,
    Ambulance,
    ArrowLeft,
    ArrowRight,
    Building2,
    CalendarDays,
    ClipboardList,
    Clock3,
    FileStack,
    Hash,
    Home,
    MapPinned,
    Mars,
    SquarePen,
    Stethoscope,
    UserRound,
    Venus,
} from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';

import ClinicalInfo from './Clinical';
import type { BreadcrumbItem } from './types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Incoming Referral', href: '/incoming' },
    { title: 'Patient profile', href: '/incoming/profile' },
];

type Profile = {
    fname: string;
    mname: string;
    lname: string;
    dob: string;
    age: string;
    avatar: string | null;
    sex?: string;
};

type Demographics = {
    street?: string;
    regname?: string;
    provname?: string;
    barangay?: string;
    zipcode?: string;
    region?: string;
    province?: string;
    city?: string;
    streetaddress?: string;
};

type Referral = {
    LogID: string;
    reason: string;
    type: string;
    category: string;
    date: string;
};

type ReferralFacility = {
    facility_name: string;
    hfhudcode: string;
};

type IncomingProfileProps = {
    id: string;
    is_include?: boolean | null;
    canEdit?: boolean;
};

type TabKey = 'overview' | 'clinical' | 'activity' | 'attachments';

const tabs: Array<{ key: TabKey; label: string; icon: typeof Activity }> = [
    { key: 'overview', label: 'Overview', icon: UserRound },
    { key: 'clinical', label: 'Clinical', icon: Stethoscope },
    { key: 'activity', label: 'Activity', icon: Activity },
    { key: 'attachments', label: 'Attachments', icon: FileStack },
];

export default function IncomingProfile({ id: logID, canEdit = false }: IncomingProfileProps) {
    const [profile, setProfile] = useState<Profile | null>(null);
    const [demographics, setDemographics] = useState<Demographics | null>(null);
    const [referralOrigin, setReferralOrigin] = useState<ReferralFacility | null>(null);
    const [referralDestination, setReferralDestination] = useState<ReferralFacility | null>(null);
    const [referral, setReferral] = useState<Referral | null>(null);
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState<TabKey>('overview');

    useEffect(() => {
        if (!logID) {
            return;
        }

        const fetchProfile = async () => {
            try {
                setLoading(true);

                const [patientResponse, referralResponse] = await Promise.all([
                    axios.get(`/patient-profile/${logID}`),
                    axios.get(`/referral-information/${logID}`),
                ]);

                setProfile(patientResponse.data.profile ?? null);
                setDemographics(patientResponse.data.demographics ?? null);
                setReferral(referralResponse.data.referral_info ?? null);
                setReferralOrigin(referralResponse.data.origin ?? null);
                setReferralDestination(referralResponse.data.destination ?? null);
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'Failed to load incoming referral profile.', 'error');
            } finally {
                setLoading(false);
            }
        };

        void fetchProfile();
    }, [logID]);

    const patientName = useMemo(() => {
        return [profile?.fname, profile?.mname, profile?.lname].filter(Boolean).join(' ');
    }, [profile]);

    const patientInitials = useMemo(() => {
        return `${profile?.fname?.charAt(0) ?? ''}${profile?.lname?.charAt(0) ?? ''}`.trim().toUpperCase() || '?';
    }, [profile]);

    const patientAddress = useMemo(() => {
        return [
            demographics?.streetaddress || demographics?.street,
            demographics?.barangay,
            demographics?.city,
            demographics?.province,
        ]
            .filter(Boolean)
            .join(', ');
    }, [demographics]);

    const receivingFocus =
        referral?.category === 'Emergency'
            ? 'Prioritize immediate receiving readiness, validate urgency, and align contact handoff without delay.'
            : 'Confirm destination coordination, verify the referral reason, and keep the receiving workflow ready for handoff.';

    const categoryTone =
        referral?.category === 'Emergency'
            ? 'border-rose-300/35 bg-rose-500/15 text-rose-50'
            : 'border-emerald-300/35 bg-emerald-400/15 text-emerald-50';

    const activityItems = [
        {
            title: 'Referral logged',
            description: referral?.date ? `Case entered on ${referral.date}.` : 'Referral date not available yet.',
            icon: CalendarDays,
        },
        {
            title: 'Routing prepared',
            description:
                referralOrigin?.facility_name && referralDestination?.facility_name
                    ? `${referralOrigin.facility_name} endorsed the patient to ${referralDestination.facility_name}.`
                    : 'Facility routing details are still loading.',
            icon: ArrowRight,
        },
        {
            title: 'Incoming queue status',
            description: 'This referral is currently visible from the incoming work queue for receiving review.',
            icon: Clock3,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incoming Referral Profile" />

            <div className="flex flex-1 flex-col gap-5 p-4 md:p-6">
                <section className="relative overflow-hidden rounded-[2rem] border border-slate-200/80 bg-gradient-to-br from-slate-950 via-teal-950 to-emerald-900 p-6 text-white shadow-xl shadow-teal-950/10">
                    <div className="absolute top-0 -left-10 h-36 w-36 rounded-full bg-cyan-300/10 blur-3xl" />
                    <div className="absolute right-0 bottom-0 h-44 w-44 rounded-full bg-emerald-200/10 blur-3xl" />

                    <div className="relative space-y-5">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div className="space-y-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge className="border-white/15 bg-white/10 text-white">Incoming Referral Profile</Badge>
                                </div>

                                <div className="space-y-2">
                                    <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                        {patientName || 'Loading patient details...'}
                                    </h1>
                                    <p className="max-w-3xl text-sm leading-6 text-teal-50/80 sm:text-base">
                                        Review patient identity, referral routing, and clinical context from one receiving-side workspace.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 lg:items-end">
                                <div className="flex flex-wrap gap-2 lg:justify-end">
                                    {referral?.LogID && (
                                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90">
                                            <Hash className="size-3.5" />
                                            {referral.LogID}
                                        </span>
                                    )}
                                    {referral?.date && (
                                        <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-white/90">
                                            <CalendarDays className="size-3.5" />
                                            {referral.date}
                                        </span>
                                    )}
                                    {referral?.category && (
                                        <span className={cn('inline-flex rounded-full border px-3 py-1.5 text-xs font-medium', categoryTone)}>
                                            {referral.category}
                                        </span>
                                    )}
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    {canEdit && (
                                        <Button asChild variant="outline" className="border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                                            <Link href={`/referrals/edit/${logID}`}>
                                                <SquarePen className="size-4" />
                                                Edit referral
                                            </Link>
                                        </Button>
                                    )}

                                    <Button asChild variant="outline" className="border-white/20 bg-white/10 text-white hover:bg-white/15 hover:text-white">
                                        <Link href="/incoming">
                                            <ArrowLeft className="size-4" />
                                            Back to queue
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                            <div className="rounded-[1.75rem] border border-white/10 bg-white/8 p-5 backdrop-blur-sm">
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                                    <div className="flex h-20 w-20 shrink-0 items-center justify-center rounded-[1.5rem] border border-white/15 bg-emerald-200/20 text-2xl font-semibold text-white shadow-lg shadow-slate-950/15">
                                        {patientInitials}
                                    </div>

                                    <div className="min-w-0 flex-1 space-y-3">
                                        <div className="space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="inline-flex items-center gap-2 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-xs font-medium text-emerald-50/95">
                                                    <UserRound className="size-3.5" />
                                                    Patient Summary
                                                </span>
                                            </div>

                                            <div>
                                                <h2 className="truncate text-xl font-semibold tracking-tight text-white sm:text-2xl">
                                                    {patientName || 'No patient name available'}
                                                </h2>
                                                <p className="mt-1 max-w-2xl text-sm leading-6 text-teal-50/75">
                                                    {patientAddress || 'No address details available for this incoming referral.'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <span className="inline-flex items-center rounded-full border border-white/15 bg-slate-950/20 px-3 py-1 text-xs font-medium text-white/90">
                                                Age {profile?.age || 'N/A'}
                                            </span>
                                            <span className="inline-flex items-center gap-2 rounded-full border border-white/15 bg-slate-950/20 px-3 py-1 text-xs font-medium text-white/90">
                                                <CalendarDays className="size-3.5" />
                                                {profile?.dob || 'Birthdate unavailable'}
                                            </span>
                                            {profile?.sex && (
                                                <span
                                                    className={cn(
                                                        'inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium',
                                                        profile.sex === 'Male'
                                                            ? 'border-sky-300/35 bg-sky-400/15 text-sky-50'
                                                            : 'border-pink-300/35 bg-pink-400/15 text-pink-50',
                                                    )}
                                                >
                                                    {profile.sex === 'Male' ? <Mars className="size-3.5" /> : <Venus className="size-3.5" />}
                                                    {profile.sex}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-5 grid gap-3 md:grid-cols-2">
                                    <div className="rounded-[1.35rem] border border-white/10 bg-slate-950/20 p-4">
                                        <div className="flex items-center gap-2">
                                            <MapPinned className="size-4 text-emerald-200" />
                                            <p className="text-sm font-semibold text-white">Demographics</p>
                                        </div>

                                        <div className="mt-3 grid grid-cols-2 gap-3 text-sm text-teal-50/80">
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-teal-100/55">Region</p>
                                                <p className="mt-1 truncate text-white">{demographics?.region || 'N/A'}</p>
                                            </div>
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-teal-100/55">Province</p>
                                                <p className="mt-1 truncate text-white">{demographics?.province || 'N/A'}</p>
                                            </div>
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-teal-100/55">City</p>
                                                <p className="mt-1 truncate text-white">{demographics?.city || 'N/A'}</p>
                                            </div>
                                            <div>
                                                <p className="text-[11px] uppercase tracking-[0.18em] text-teal-100/55">Barangay</p>
                                                <p className="mt-1 truncate text-white">{demographics?.barangay || 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[1.35rem] border border-white/10 bg-slate-950/20 p-4">
                                        <div className="flex items-center gap-2">
                                            <Home className="size-4 text-cyan-200" />
                                            <p className="text-sm font-semibold text-white">Address Details</p>
                                        </div>

                                        <div className="mt-3 space-y-3 text-sm leading-6 text-teal-50/80">
                                            <p className="text-white/90">{patientAddress || 'No address details available.'}</p>
                                            <div className="flex items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2">
                                                <span className="text-[11px] uppercase tracking-[0.18em] text-teal-100/55">Zipcode</span>
                                                <span className="font-medium text-white">{demographics?.zipcode || 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="grid gap-4">
                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                        <div className="flex items-center gap-2">
                                            <ClipboardList className="size-4 text-amber-200" />
                                            <p className="text-sm font-semibold text-white">Referral Summary</p>
                                        </div>

                                        <div className="mt-4 space-y-3 text-sm text-teal-50/80">
                                            <div className="flex items-start justify-between gap-3">
                                                <span className="text-teal-100/55">Type</span>
                                                <span className="text-right font-medium text-white">{referral?.type || 'N/A'}</span>
                                            </div>
                                            <div className="flex items-start justify-between gap-3">
                                                <span className="text-teal-100/55">Reason</span>
                                                <span className="text-right font-medium text-white">{referral?.reason || 'N/A'}</span>
                                            </div>
                                            <div className="flex items-start justify-between gap-3">
                                                <span className="text-teal-100/55">Category</span>
                                                <span className="text-right font-medium text-white">{referral?.category || 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                        <div className="flex items-center gap-2">
                                            <Ambulance className="size-4 text-emerald-200" />
                                            <p className="text-sm font-semibold text-white">Receiving Focus</p>
                                        </div>

                                        <p className="mt-4 text-sm leading-7 text-teal-50/80">{receivingFocus}</p>
                                    </div>
                                </div>

                                <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-4 backdrop-blur-sm">
                                    <div className="flex items-center gap-2">
                                        <Building2 className="size-4 text-cyan-200" />
                                        <p className="text-sm font-semibold text-white">Facility Route</p>
                                    </div>

                                    <div className="mt-4 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] sm:items-center">
                                        <div className="min-w-0 rounded-[1.25rem] border border-white/10 bg-slate-950/20 px-4 py-3">
                                            <p className="text-[11px] uppercase tracking-[0.24em] text-teal-100/55">Origin</p>
                                            <p className="mt-2 truncate text-sm font-medium text-white">{referralOrigin?.facility_name || 'N/A'}</p>
                                            <p className="mt-1 text-xs text-teal-50/65">{referralOrigin?.hfhudcode || 'No facility code'}</p>
                                        </div>

                                        <div className="flex items-center justify-center text-teal-100/60">
                                            <ArrowRight className="size-4" />
                                        </div>

                                        <div className="min-w-0 rounded-[1.25rem] border border-white/10 bg-slate-950/20 px-4 py-3">
                                            <p className="text-[11px] uppercase tracking-[0.24em] text-teal-100/55">Destination</p>
                                            <p className="mt-2 truncate text-sm font-medium text-white">{referralDestination?.facility_name || 'N/A'}</p>
                                            <p className="mt-1 text-xs text-teal-50/65">{referralDestination?.hfhudcode || 'No facility code'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {loading ? (
                    <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                        <CardContent className="flex min-h-56 items-center justify-center p-6 text-sm text-slate-500">
                            Loading incoming referral profile...
                        </CardContent>
                    </Card>
                ) : (
                    <>
                        <section className="grid gap-4 xl:grid-cols-[220px_minmax(0,1fr)]">
                            <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                                <CardContent className="p-3">
                                    <nav className="grid gap-2">
                                        {tabs.map((tab) => {
                                            const Icon = tab.icon;

                                            return (
                                                <button
                                                    key={tab.key}
                                                    type="button"
                                                    onClick={() => setActiveTab(tab.key)}
                                                    className={cn(
                                                        'flex items-center gap-3 rounded-2xl px-4 py-3 text-left text-sm font-medium transition',
                                                        activeTab === tab.key
                                                            ? 'bg-teal-700 text-white shadow-sm'
                                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                                    )}
                                                >
                                                    <Icon className="size-4" />
                                                    {tab.label}
                                                </button>
                                            );
                                        })}
                                    </nav>
                                </CardContent>
                            </Card>

                            <div className="space-y-4">
                                {activeTab === 'overview' && (
                                    <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                                        <CardContent className="space-y-5 p-6">
                                            <div>
                                                <h2 className="text-lg font-semibold text-slate-900">Overview</h2>
                                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                                    Core patient identity and referral routing are now summarized in the header above. Use this section for intake posture and coordination notes.
                                                </p>
                                            </div>

                                            <div className="grid gap-4 md:grid-cols-2">
                                                <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/80 p-4">
                                                    <p className="text-sm font-semibold text-slate-900">Receiving focus</p>
                                                    <p className="mt-3 text-sm leading-6 text-slate-600">
                                                        {referral?.category === 'Emergency'
                                                            ? 'Prioritize immediate receiving readiness, confirm contact alignment, and assess urgency as soon as the case is reviewed.'
                                                            : 'Prepare receiving coordination, validate the referral reason, and keep the destination workflow aligned for handoff.'}
                                                    </p>
                                                </div>

                                                <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/80 p-4">
                                                    <p className="text-sm font-semibold text-slate-900">Coordination note</p>
                                                    <p className="mt-3 text-sm leading-6 text-slate-600">
                                                        {referralOrigin?.facility_name && referralDestination?.facility_name
                                                            ? `${referralOrigin.facility_name} routed this case to ${referralDestination.facility_name} for ${referral?.reason || 'further receiving review'}.`
                                                            : 'Routing context is still being prepared for this referral.'}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="rounded-[1.5rem] border border-teal-100 bg-teal-50/70 p-4">
                                                <p className="text-sm font-semibold text-slate-900">Next receiving step</p>
                                                <p className="mt-2 text-sm leading-6 text-slate-600">
                                                    Review the clinical tab, confirm destination readiness, and proceed with receiving-side triage based on the referral category and handoff reason.
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}

                                {activeTab === 'clinical' && (
                                    <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                                        <CardContent className="p-6">
                                            <div className="mb-5">
                                                <h2 className="text-lg font-semibold text-slate-900">Clinical Information</h2>
                                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                                    Working clinical notes and vital signs attached to this referral.
                                                </p>
                                            </div>

                                            <ClinicalInfo logID={logID} />
                                        </CardContent>
                                    </Card>
                                )}

                                {activeTab === 'activity' && (
                                    <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                                        <CardContent className="space-y-4 p-6">
                                            <div>
                                                <h2 className="text-lg font-semibold text-slate-900">Referral Activity</h2>
                                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                                    A lightweight event trail for the current incoming referral context.
                                                </p>
                                            </div>

                                            <div className="space-y-3">
                                                {activityItems.map((item, index) => {
                                                    const Icon = item.icon;

                                                    return (
                                                        <div key={item.title} className="flex gap-4 rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                                                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-teal-700 shadow-sm">
                                                                <Icon className="size-4" />
                                                            </div>
                                                            <div className="min-w-0">
                                                                <div className="flex items-center gap-2">
                                                                    <p className="font-medium text-slate-900">{item.title}</p>
                                                                    {index === activityItems.length - 1 && (
                                                                        <Badge variant="outline" className="rounded-full border-amber-200 bg-amber-50 text-amber-700">
                                                                            Current
                                                                        </Badge>
                                                                    )}
                                                                </div>
                                                                <p className="mt-1 text-sm leading-6 text-slate-600">{item.description}</p>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}

                                {activeTab === 'attachments' && (
                                    <Card className="border-slate-200/80 bg-white/90 shadow-sm">
                                        <CardContent className="p-6">
                                            <div>
                                                <h2 className="text-lg font-semibold text-slate-900">Attachments</h2>
                                                <p className="mt-1 text-sm leading-6 text-slate-500">
                                                    Supporting files and uploads linked to this referral will appear here.
                                                </p>
                                            </div>

                                            <div className="mt-5 rounded-[1.5rem] border border-dashed border-slate-200 bg-slate-50/70 p-8 text-center">
                                                <FileStack className="mx-auto size-8 text-slate-400" />
                                                <p className="mt-3 text-sm font-medium text-slate-700">No attachments available</p>
                                                <p className="mt-1 text-sm text-slate-500">
                                                    This profile does not have uploaded referral attachments yet.
                                                </p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}
                            </div>
                        </section>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
