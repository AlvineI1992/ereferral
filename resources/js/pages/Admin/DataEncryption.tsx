import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { activateEncryption, getEncryptionStatus, type EncryptionState } from '@/services/data-encryption-service';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { Database, KeyRound, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import Swal from 'sweetalert2';

function EncryptionPanel({ initialState, patient = false }: { initialState: EncryptionState; patient?: boolean }) {
    const endpoint = patient ? '/admin/data-encryption/patients' : '/admin/data-encryption';
    const phrase = patient ? 'ENABLE PATIENT ENCRYPTION' : 'ENABLE ENCRYPTION';
    const [encryption, setEncryption] = useState(initialState);
    const [confirmation, setConfirmation] = useState('');
    const [saving, setSaving] = useState(false);
    const ready = Object.values(encryption.preflight).every(Boolean);

    useEffect(() => {
        if (encryption.status !== 'converting') return;

        const controller = new AbortController();
        const interval = window.setInterval(async () => {
            try {
                setEncryption(await getEncryptionStatus(endpoint, controller.signal));
            } catch {
                // Keep the last known state; the next poll can recover from a transient failure.
            }
        }, 2000);

        return () => {
            window.clearInterval(interval);
            controller.abort();
        };
    }, [encryption.status, endpoint]);

    const requestActivation = async () => {
        setSaving(true);
        try {
            const response = await activateEncryption(endpoint, confirmation);
            setEncryption(response.encryption);
            setConfirmation('');
            await Swal.fire('Activation started', response.message, 'success');
        } catch (error) {
            if (axios.isAxiosError(error)) {
                await Swal.fire('Activation not started', error.response?.data?.message ?? 'Unable to activate encryption.', 'warning');
            }
        } finally {
            setSaving(false);
        }
    };

    const checks = [
        ['Encryption key', encryption.preflight.keyConfigured, KeyRound],
        ['Blind indexes', encryption.preflight.blindIndexesReady, Database],
        ['Module storage', encryption.preflight.settingsReady, LockKeyhole],
        ['Resumable converter', encryption.preflight.converterReady, Database],
    ] as const;

    return (
        <Card>
            <CardContent className="space-y-4 p-5">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-3">
                        <div className="rounded-xl bg-teal-50 p-3 text-teal-700">
                            <ShieldCheck className="size-5" />
                        </div>
                        <div>
                            <p className="font-semibold text-slate-900">{patient ? 'Patient information (PII)' : 'User email addresses'}</p>
                            <p className="text-sm text-slate-500">
                                Current status: {encryption.enabled ? 'Enabled' : 'Off'} · {encryption.status}
                            </p>
                        </div>
                    </div>
                    <span
                        className={`rounded-full px-3 py-1 text-xs font-semibold ${encryption.enabled ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
                    >
                        {encryption.enabled ? 'ON' : 'OFF'}
                    </span>
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {checks.map(([label, passed, Icon]) => (
                        <div key={label} className="rounded-xl border p-3">
                            <Icon className="size-4 text-slate-500" />
                            <p className="mt-2 text-sm font-medium">{label}</p>
                            <p className={`text-xs ${passed ? 'text-emerald-600' : 'text-rose-600'}`}>{passed ? 'Ready' : 'Not ready'}</p>
                        </div>
                    ))}
                </div>

                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    {patient
                        ? 'Protects names, birth dates, contact numbers, family/case/PhilHealth identifiers, street addresses, ZIP codes, and referral journey snapshots. Search using an exact name or identifier; patient records appear newest first. Geographic codes and clinical source records retain their existing storage.'
                        : 'Protects user email addresses.'}{' '}
                    Activation verifies an encrypted backup first. Keep the queue worker running until conversion completes.
                </div>

                {encryption.status === 'converting' && (
                    <div className="text-sm text-slate-600">
                        Converted {encryption.processedRows} of {encryption.totalRows} records.
                    </div>
                )}
                {encryption.lastError && <div className="text-sm text-rose-600">{encryption.lastError}</div>}

                <div className="flex flex-col gap-2 sm:flex-row">
                    <Input
                        value={confirmation}
                        onChange={(event) => setConfirmation(event.target.value)}
                        placeholder={`Type ${phrase}`}
                        aria-label={`Confirmation for ${patient ? 'patient' : 'email'} encryption`}
                    />
                    <Button
                        disabled={!ready || saving || encryption.enabled || encryption.status === 'converting' || confirmation !== phrase}
                        onClick={requestActivation}
                    >
                        {saving
                            ? 'Checking…'
                            : encryption.enabled
                              ? 'Encryption enabled'
                              : encryption.status === 'converting'
                                ? 'Converting…'
                                : 'Enable encryption'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

export default function DataEncryption({ encryption, patientEncryption }: { encryption: EncryptionState; patientEncryption: EncryptionState }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Data Encryption', href: '/admin/data-encryption' }]}>
            <Head title="Data Encryption" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold text-slate-900">CipherSweet Data Encryption</h1>
                    <p className="mt-1 text-sm text-slate-500">Control field-level encryption and monitor conversion of sensitive records.</p>
                </div>
                <EncryptionPanel initialState={encryption} />
                <EncryptionPanel initialState={patientEncryption} patient />
            </div>
        </AppLayout>
    );
}
