import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { maintenanceStatus, runMaintenance, type MaintenanceStatus } from '@/services/database-maintenance';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import Swal from 'sweetalert2';

export default function DatabaseMaintenance({ maintenance }: { maintenance: MaintenanceStatus }) {
    const [status, setStatus] = useState(maintenance);
    const [seeder, setSeeder] = useState(maintenance.seeders[0]?.key ?? '');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const running = busy || status.running;
    useEffect(() => {
        if (!status.running) return;
        const timer = window.setInterval(() => {
            void maintenanceStatus()
                .then(setStatus)
                .catch(() => setError('Unable to refresh running operation.'));
        }, 3000);
        return () => window.clearInterval(timer);
    }, [status.running]);
    const pending = status.migrations.filter((migration) => !migration.ran);

    const refresh = async () => {
        try {
            setStatus(await maintenanceStatus());
        } catch {
            setError('Unable to refresh maintenance status.');
        }
    };
    const run = async (action: 'migrate' | 'seed') => {
        if (running) return;
        const label =
            action === 'migrate' ? `Run ${pending.length} pending migrations` : `Run ${status.seeders.find((item) => item.key === seeder)?.label}`;
        const result = await Swal.fire({
            title: label,
            text: 'This changes the current application database. Continue?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Run now',
        });
        if (!result.isConfirmed) return;
        setBusy(true);
        setError('');
        try {
            await runMaintenance(action, seeder);
        } catch (cause: unknown) {
            setError(
                axios.isAxiosError(cause)
                    ? (cause.response?.data?.result?.output ??
                          cause.response?.data?.message ??
                          'The request was interrupted. Refresh status before trying again.')
                    : 'Unable to run maintenance.',
            );
        } finally {
            await refresh();
            setBusy(false);
        }
    };
    return (
        <AppLayout breadcrumbs={[{ title: 'Database Maintenance', href: '/admin/database-maintenance' }]}>
            <Head title="Database Maintenance" />
            <div className="space-y-4 p-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Database Maintenance</h1>
                        <p className="text-muted-foreground text-sm">Apply pending migrations and seed application permissions.</p>
                    </div>
                    <Button variant="outline" disabled={busy} onClick={() => void refresh()}>
                        Refresh status
                    </Button>
                </div>
                {error && (
                    <p className="text-destructive text-sm" role="alert">
                        {error}
                    </p>
                )}
                {running && (
                    <p className="text-sm" role="status">
                        Running maintenance. Keep this page open until the result appears.
                    </p>
                )}
                <div className="grid gap-4 lg:grid-cols-2">
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="font-semibold">Migrations</h2>
                        <p className="text-sm">
                            {pending.length} pending · {status.migrations.length - pending.length} applied
                        </p>
                        <div className="max-h-64 overflow-auto">
                            <ul className="space-y-1 text-xs">
                                {pending.map((migration) => (
                                    <li key={migration.name} className="whitespace-nowrap">
                                        {migration.name}
                                    </li>
                                ))}
                            </ul>
                            {!pending.length && <p className="text-muted-foreground text-sm">Database schema is up to date.</p>}
                        </div>
                        <Button disabled={running || !pending.length} onClick={() => void run('migrate')}>
                            Run pending migrations
                        </Button>
                    </section>
                    <section className="space-y-3 rounded-xl border p-4">
                        <h2 className="font-semibold">Seeders</h2>
                        <label className="block space-y-1 text-sm">
                            <span>Seeder</span>
                            <select
                                value={seeder}
                                disabled={running}
                                onChange={(event) => setSeeder(event.target.value)}
                                className="bg-background h-9 w-full rounded-md border px-2"
                            >
                                {status.seeders.map((item) => (
                                    <option key={item.key} value={item.key}>
                                        {item.label}
                                    </option>
                                ))}
                            </select>
                        </label>
                        <Button disabled={running || !seeder} onClick={() => void run('seed')}>
                            Run selected seeder
                        </Button>
                    </section>
                </div>
                <section className="space-y-3">
                    <h2 className="font-semibold">Recent runs</h2>
                    {!status.history.length && <p className="text-muted-foreground text-sm">No maintenance runs recorded.</p>}
                    {status.history.map((run) => (
                        <details key={run.id} className="rounded-lg border p-3" open={run.status === 'failed'}>
                            <summary className="cursor-pointer text-sm">
                                {run.action === 'migrate' ? 'Migrations' : run.seeder} · {run.status} · {new Date(run.started_at).toLocaleString()} ·
                                User #{run.actor_id}
                            </summary>
                            <pre className="bg-muted mt-3 max-h-72 overflow-auto rounded p-3 text-xs whitespace-pre-wrap">
                                {run.output || 'Execution started; refresh status for the result.'}
                            </pre>
                        </details>
                    ))}
                </section>
            </div>
        </AppLayout>
    );
}
