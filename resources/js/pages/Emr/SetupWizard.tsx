import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { UserRecord } from '@/pages/users/types';
import UsersForm from '@/pages/users/usersForm';
import UsersListAssign from '@/pages/users/UsersListAssign';
import { createSetupProvider, generateSetupToken } from '@/services/provider-setup';
import { Head, Link } from '@inertiajs/react';
import axios from 'axios';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import Form from './Form';
import type { EmrRecord } from './types';

export default function SetupWizard() {
    const [provider, setProvider] = useState<EmrRecord | null>(null);
    const [user, setUser] = useState<UserRecord | null>(null);
    const [complete, setComplete] = useState(false);
    const [rolesSaved, setRolesSaved] = useState(false);
    const [token, setToken] = useState('');
    const [generating, setGenerating] = useState(false);
    const [revealed, setRevealed] = useState(false);
    const [tokenError, setTokenError] = useState('');
    const step = !provider ? 0 : !user ? 1 : !rolesSaved ? 2 : 3;
    const providerOption = useMemo(() => (provider ? { emr_id: String(provider.emr_id), emr_name: provider.emr_name } : undefined), [provider]);

    const generateToken = async () => {
        if (!user || generating || token) return;
        setGenerating(true);
        setTokenError('');
        try {
            setToken(await generateSetupToken(user.id));
            setComplete(true);
        } catch (error: unknown) {
            setTokenError(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'Unable to generate token. Please retry.')
                    : 'Unable to generate token. Please retry.',
            );
        } finally {
            setGenerating(false);
        }
    };

    const copyToken = async () => {
        try {
            await navigator.clipboard.writeText(token);
            toast.success('EMR token copied.');
        } catch {
            setRevealed(true);
            toast.error('Clipboard unavailable. Select and copy the token manually.');
        }
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Provider', href: '/emr' },
                { title: 'Setup Wizard', href: '/provider-setup' },
            ]}
        >
            <Head title="Provider Setup Wizard" />
            <div className="mx-auto w-full max-w-4xl space-y-4 p-4">
                <h1 className="text-xl font-semibold">Provider Setup Wizard</h1>
                <p className="text-muted-foreground text-sm">
                    Each step saves immediately. Saved records remain available if you leave before completing setup.
                </p>
                <ol className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4" aria-label="Setup progress">
                    {['Add Provider', 'Add User', 'Role Assignment', 'Generate EMR Token'].map((label, index) => (
                        <li
                            key={label}
                            aria-current={!complete && index === step ? 'step' : undefined}
                            className={cn(
                                'rounded-lg border px-3 py-2 text-sm',
                                index === step ? 'border-primary bg-primary/10 font-semibold' : 'text-muted-foreground',
                            )}
                        >
                            {index + 1}. {label}
                            {index < step || complete ? ' — Saved' : ''}
                        </li>
                    ))}
                </ol>
                {provider && (
                    <p className="text-sm">
                        Provider: <strong>{provider.emr_name}</strong>
                        {user && (
                            <>
                                {' '}
                                · User: <strong>{user.name}</strong> ({user.email})
                            </>
                        )}
                    </p>
                )}
                {step === 0 && (
                    <Form
                        canCreate
                        canEdit={false}
                        emr={null}
                        onCancel={() => {}}
                        onCreated={() => {}}
                        saveProvider={async (values) => {
                            setProvider(await createSetupProvider(values));
                        }}
                    />
                )}
                {step === 1 && (
                    <UsersForm canCreate canEdit={false} user={null} provider={providerOption} onCancel={() => {}} onUserCreated={setUser} />
                )}
                {step === 2 && user && <UsersListAssign id={user.id} is_include refreshKey={0} onSave={() => setRolesSaved(true)} />}
                {step === 3 && (
                    <section className="space-y-3 rounded-lg border p-4" aria-label="EMR token generation">
                        <h2 className="font-semibold">Generate EMR Token</h2>
                        <p className="text-muted-foreground text-sm">
                            Generate a token for this user and provider. Copy it before leaving; the full token is shown only when generated.
                        </p>
                        {!token ? (
                            <Button disabled={generating} onClick={() => void generateToken()}>
                                {generating ? 'Generating...' : 'Generate EMR Token'}
                            </Button>
                        ) : (
                            <div className="space-y-2">
                                <Label htmlFor="setup-emr-token">EMR token</Label>
                                <Input
                                    id="setup-emr-token"
                                    readOnly
                                    type={revealed ? 'text' : 'password'}
                                    value={token}
                                    autoComplete="off"
                                    spellCheck={false}
                                    onFocus={(event) => event.target.select()}
                                />
                                <div className="flex flex-wrap gap-2">
                                    <Button variant="outline" onClick={() => setRevealed(!revealed)}>
                                        {revealed ? 'Hide token' : 'Reveal token'}
                                    </Button>
                                    <Button onClick={() => void copyToken()}>Copy token</Button>
                                </div>
                                <p className="text-muted-foreground text-xs">
                                    Use this value in the X-EMR-Token header together with the account's Bearer token.
                                </p>
                            </div>
                        )}
                        {tokenError && (
                            <p className="text-destructive text-sm" role="alert">
                                {tokenError}
                            </p>
                        )}
                    </section>
                )}
                {complete && (
                    <div className="space-y-3 rounded-lg border p-4" role="status">
                        <p className="font-medium">Setup complete. The provider, user, roles, and EMR credential have been saved.</p>
                        <Button asChild variant="outline">
                            <Link href={`/users/assigned-roles/${user?.id}`}>View assigned roles</Link>
                        </Button>
                        <Button
                            onClick={() => {
                                setProvider(null);
                                setUser(null);
                                setComplete(false);
                                setRolesSaved(false);
                                setToken('');
                                setRevealed(false);
                                setTokenError('');
                            }}
                        >
                            Set up another provider
                        </Button>
                    </div>
                )}
                <Button asChild variant="outline">
                    <Link href="/emr">Return to providers</Link>
                </Button>
            </div>
        </AppLayout>
    );
}
