import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ArrowRight, HeartPulse, Landmark, ShieldCheck } from 'lucide-react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: React.ReactNode;
    description?: string;
}

const highlights = [
    {
        icon: ShieldCheck,
        title: 'Secure access',
        description: 'Session-protected sign-in for referral workflows and protected facility records.',
    },
    {
        icon: HeartPulse,
        title: 'Faster coordination',
        description: 'Keep patient handoff details, receiving contacts, and routing in one place.',
    },
    {
        icon: Landmark,
        title: 'Public health aligned',
        description: 'Built around referral operations for connected hospitals and partner facilities.',
    },
];

export default function AuthSplitLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { quote } = usePage<SharedData>().props;

    return (
        <div className="relative h-svh overflow-hidden bg-[linear-gradient(145deg,#f6fbff_0%,#edf7f2_52%,#f8fbf3_100%)] text-slate-950">
            <div className="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(15,118,110,0.14),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(22,101,52,0.14),transparent_28%)]" />

            <div className="relative grid h-svh lg:grid-cols-[1.08fr_0.92fr]">
                <section className="relative hidden overflow-hidden lg:flex">
                    <img
                        src="/doh.jpg"
                        alt="Department of Health"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                    <div className="absolute inset-0 bg-[linear-gradient(140deg,rgba(2,44,34,0.9)_0%,rgba(15,118,110,0.78)_48%,rgba(20,83,45,0.82)_100%)]" />
                    <div className="absolute inset-0 bg-[radial-gradient(circle_at_18%_18%,rgba(255,255,255,0.18),transparent_26%),radial-gradient(circle_at_82%_18%,rgba(255,255,255,0.12),transparent_22%),radial-gradient(circle_at_50%_82%,rgba(255,255,255,0.1),transparent_24%)]" />

                    <div className="relative z-10 flex h-full w-full flex-col justify-between p-8 xl:p-10">
                        <div className="space-y-7">
                            <Link href={route('home')} className="inline-flex items-center gap-4 text-white">
                                <img src="/doh-logo.png" alt="Department of Health logo" className="h-16 w-16 rounded-2xl bg-white/10 p-2 backdrop-blur-sm" />
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-[0.28em] text-teal-100/90">Department Of Health</p>
                                    <h2 className="mt-1 text-2xl font-semibold">eReferral System</h2>
                                </div>
                            </Link>

                            <div className="max-w-xl space-y-4">
                                <span className="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-white/85 backdrop-blur-sm">
                                    Referral Operations Portal
                                </span>
                                <h1 className="text-3xl font-semibold leading-tight text-white xl:text-4xl">
                                    Coordinate referrals with clarity from first call to receiving facility.
                                </h1>
                                <p className="max-w-lg text-sm leading-6 text-teal-50/88 xl:text-base">
                                    A focused workspace for triage teams, hospitals, and regional partners managing patient movement and handoff details.
                                </p>
                            </div>

                            <div className="grid gap-3">
                                {highlights.map((item) => {
                                    const Icon = item.icon;

                                    return (
                                        <div
                                            key={item.title}
                                            className="flex items-start gap-4 rounded-3xl border border-white/14 bg-white/10 p-4 backdrop-blur-md"
                                        >
                                            <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/14">
                                                <Icon className="h-5 w-5 text-white" />
                                            </div>
                                            <div>
                                                <p className="text-sm font-semibold uppercase tracking-[0.2em] text-teal-100">{item.title}</p>
                                                <p className="mt-1.5 text-sm leading-6 text-white/82">{item.description}</p>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="flex items-end justify-between gap-6 rounded-[2rem] border border-white/14 bg-black/15 p-5 text-white backdrop-blur-md">
                            <div className="max-w-md">
                                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-teal-100">Connected Facilities</p>
                                <p className="mt-2 text-sm leading-6 text-white/80">
                                    Keep incoming referrals, facility routing, and receiving contact coordination inside one operational view.
                                </p>
                            </div>
                            <div className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/20 bg-white/10">
                                <ArrowRight className="h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </section>

                <section className="relative flex h-full items-center justify-center overflow-hidden px-4 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-6 xl:px-10">
                    <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(255,255,255,0.76),rgba(255,255,255,0.9))]" />
                    <div className="pointer-events-none absolute inset-x-0 top-0 h-40 bg-[radial-gradient(circle_at_top,rgba(13,148,136,0.14),transparent_68%)]" />

                    <div className="relative z-10 flex h-full w-full max-w-2xl flex-col justify-center">
                        <div className="mb-4 flex items-center justify-center lg:hidden">
                            <Link
                                href={route('home')}
                                className="inline-flex items-center gap-3 rounded-full border border-teal-100 bg-white/85 px-4 py-2 shadow-sm backdrop-blur"
                            >
                                <img src="/doh-logo.png" alt="Department of Health logo" className="h-10 w-10" />
                                <div className="text-left">
                                    <p className="text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700">DOH eReferral</p>
                                    <p className="text-sm font-semibold text-slate-900">Secure sign-in</p>
                                </div>
                            </Link>
                        </div>

                        <div className="w-full">
                            <div className="mb-5 space-y-3">
                                <div className="inline-flex rounded-full border border-teal-100 bg-teal-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700">
                                    Authorized Access
                                </div>
                                <div className="space-y-3">
                                    <div className="text-left text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">{title}</div>
                                <p className="max-w-xl text-sm leading-6 text-slate-600 sm:text-base">{description}</p>
                                </div>
                            </div>

                            {children}

                            {quote && (
                                <div className="mt-5 hidden max-w-xl rounded-[1.5rem] border border-emerald-100/80 bg-emerald-50/60 p-5 xl:block">
                                    <p className="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Message</p>
                                    <blockquote className="mt-3 space-y-2">
                                        <p className="text-sm leading-6 text-slate-700">&ldquo;{quote.message}&rdquo;</p>
                                        <footer className="text-xs font-medium uppercase tracking-[0.18em] text-emerald-700/80">{quote.author}</footer>
                                    </blockquote>
                                </div>
                            )}
                        </div>
                    </div>
                </section>
            </div>
        </div>
    );
}
