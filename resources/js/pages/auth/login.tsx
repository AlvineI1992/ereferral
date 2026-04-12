import { Head, useForm } from '@inertiajs/react';
import { ArrowRight, LoaderCircle, LockKeyhole, Mail, ShieldCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title={
                <div className="flex min-w-0 items-center gap-3 sm:gap-4">
                    <img src="/doh-logo.png" alt="Department of Health Official Logo" className="h-10 w-10 shrink-0 select-none sm:h-11 sm:w-11" />
                    <div className="min-w-0 space-y-1">
                        <div className="text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-700">Department Of Health</div>
                        <div className="truncate text-xl font-semibold tracking-tight text-slate-950 sm:text-2xl">
                            <span className="font-serif italic text-teal-700">e</span>Referral System
                        </div>
                    </div>
                    <img
                        src="/ihomisplus.png"
                        alt="Bagong Pilipinas"
                        className="ml-auto h-10 w-10 shrink-0 select-none rounded-full object-contain sm:h-12 sm:w-12"
                    />
                </div>
            }
            description="Sign in to manage incoming referrals, facility routing, and receiving handoff details."
        >
            <Head title="Log in" />

            <div className="space-y-5">
                <div className="grid gap-3 sm:grid-cols-3 lg:gap-2.5">
                    <div className="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                        <Mail className="h-4 w-4 text-teal-700" />
                        <p className="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Portal</p>
                        <p className="mt-1 text-sm font-medium text-slate-900">Facility access</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                        <ShieldCheck className="h-4 w-4 text-emerald-700" />
                        <p className="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Protected</p>
                        <p className="mt-1 text-sm font-medium text-slate-900">Session secured</p>
                    </div>
                    <div className="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4">
                        <LockKeyhole className="h-4 w-4 text-sky-700" />
                        <p className="mt-3 text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Workflow</p>
                        <p className="mt-1 text-sm font-medium text-slate-900">Referral monitoring</p>
                    </div>
                </div>

                {status && (
                    <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                        {status}
                    </div>
                )}

                <form className="flex flex-col gap-4" onSubmit={submit}>
                    <div className="grid gap-4">
                        <div className="grid gap-2">
                            <div className="flex items-center justify-between">
                                <Label htmlFor="email" className="text-sm font-semibold text-slate-800">
                                    Email address
                                </Label>
                                <span className="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Required</span>
                            </div>
                            <div className="relative">
                                <Mail className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    id="email"
                                    type="email"
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="email@example.com"
                                    className={cn('h-12 rounded-xl border-slate-200 bg-white pl-10', errors.email && 'border-red-400')}
                                />
                            </div>
                            <InputError message={errors.email} className="text-xs text-red-500" />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password" className="text-sm font-semibold text-slate-800">
                                    Password
                                </Label>
                                {canResetPassword && (
                                    <TextLink href={route('password.request')} className="ml-auto text-sm text-teal-700 decoration-teal-200" tabIndex={5}>
                                        Forgot password?
                                    </TextLink>
                                )}
                            </div>
                            <div className="relative">
                                <LockKeyhole className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    id="password"
                                    type="password"
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Password"
                                    className={cn('h-12 rounded-xl border-slate-200 bg-white pl-10', errors.password && 'border-red-400')}
                                />
                            </div>
                            <InputError message={errors.password} className="text-xs text-red-500" />
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-2 rounded-2xl border border-slate-200/80 bg-slate-50/80 px-4 py-3">
                            <div className="flex min-w-0 items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    checked={data.remember}
                                    onClick={() => setData('remember', !data.remember)}
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember" className="text-sm font-medium text-slate-700">
                                    Keep me signed in on this device
                                </Label>
                            </div>
                            <span className="text-xs text-slate-500 sm:text-right">Use only on trusted workstations.</span>
                        </div>

                        <Button
                            type="submit"
                            className="h-12 w-full rounded-xl bg-[linear-gradient(135deg,#0f766e_0%,#166534_100%)] text-white shadow-lg shadow-emerald-900/15 transition-transform hover:scale-[1.01] hover:opacity-95"
                            tabIndex={4}
                            disabled={processing}
                        >
                            {processing ? (
                                <>
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                    Signing in...
                                </>
                            ) : (
                                <>
                                    Log in
                                    <ArrowRight className="h-4 w-4" />
                                </>
                            )}
                        </Button>

                        <div className="rounded-2xl border border-teal-100 bg-teal-50/70 px-4 py-3 text-sm leading-6 text-slate-600">
                            Access is intended for authorized referral personnel, facility staff, and connected program users only.
                        </div>
                    </div>
                </form>

                <div className="text-center text-sm text-slate-500">
                    Don&apos;t have an account?{' '}
                    <TextLink href={route('register')} tabIndex={5} className="font-medium text-teal-700 decoration-teal-200">
                        Sign up
                    </TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
