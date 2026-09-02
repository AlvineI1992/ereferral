import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Database, KeyRound, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import Swal from 'sweetalert2';

type EncryptionState = {
    enabled: boolean;
    status: string;
    processedRows: number;
    totalRows: number;
    lastError: string | null;
    preflight: { keyConfigured: boolean; blindIndexesReady: boolean; settingsReady: boolean };
};

export default function DataEncryption({ encryption: initialState }: { encryption: EncryptionState }) {
    const [encryption, setEncryption] = useState(initialState);
    const [confirmation, setConfirmation] = useState('');
    const [saving, setSaving] = useState(false);
    const ready = Object.values(encryption.preflight).every(Boolean);

    const requestActivation = async () => {
        setSaving(true);
        try {
            const response = await axios.put('/admin/data-encryption', { enabled: true, confirmation });
            setEncryption(response.data.encryption);
        } catch (error) {
            if (axios.isAxiosError(error)) {
                await Swal.fire('Activation not started', error.response?.data?.message ?? 'Unable to activate encryption.', 'warning');
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Data Encryption', href: '/admin/data-encryption' }]}>
            <Head title="Data Encryption" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900">CipherSweet Data Encryption</h1>
                    <p className="mt-1 text-sm text-slate-500">Control field-level encryption and monitor conversion of sensitive records.</p>
                </div>

                <Card>
                    <CardContent className="space-y-4 p-5">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex items-center gap-3">
                                <div className="rounded-xl bg-teal-50 p-3 text-teal-700"><ShieldCheck className="size-5" /></div>
                                <div>
                                    <p className="font-semibold text-slate-900">Encryption switch</p>
                                    <p className="text-sm text-slate-500">Current status: {encryption.enabled ? 'Enabled' : 'Off'} · {encryption.status}</p>
                                </div>
                            </div>
                            <span className={`rounded-full px-3 py-1 text-xs font-semibold ${encryption.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                {encryption.enabled ? 'ON' : 'OFF'}
                            </span>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-3">
                            {[
                                ['Encryption key', encryption.preflight.keyConfigured, KeyRound],
                                ['Blind indexes', encryption.preflight.blindIndexesReady, Database],
                                ['Module storage', encryption.preflight.settingsReady, LockKeyhole],
                            ].map(([label, passed, Icon]) => (
                                <div key={String(label)} className="rounded-xl border p-3">
                                    <Icon className="size-4 text-slate-500" />
                                    <p className="mt-2 text-sm font-medium">{String(label)}</p>
                                    <p className={`text-xs ${passed ? 'text-emerald-600' : 'text-rose-600'}`}>{passed ? 'Ready' : 'Not ready'}</p>
                                </div>
                            ))}
                        </div>

                        <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                            Activation is intentionally locked until the resumable converter and verified-backup check are available. The switch remains off and no existing records are changed.
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Input value={confirmation} onChange={(event) => setConfirmation(event.target.value)} placeholder="Type ENABLE ENCRYPTION" />
                            <Button disabled={!ready || saving || confirmation !== 'ENABLE ENCRYPTION'} onClick={requestActivation}>
                                {saving ? 'Checking…' : 'Enable encryption'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
