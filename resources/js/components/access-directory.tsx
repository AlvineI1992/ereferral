import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    deleteAccessRecord,
    loadAccessDirectory,
    type AccessRecord,
    type DirectoryFilters,
    type DirectoryKind,
    type DirectoryResponse,
} from '@/services/access-directory';
import { Link, useForm } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import Swal from 'sweetalert2';

type Props = { kind: DirectoryKind; canCreate: boolean; canEdit: boolean; canDelete: boolean; canView: boolean; canAssign?: boolean };
const defaults: DirectoryFilters = { search: '', module: '', action: '', guard: '', assignment: '', sort: 'name', per_page: 10, page: 1 };
const empty: DirectoryResponse = { data: [], total: 0, last_page: 1, filter_options: { modules: [], actions: [], guards: [] } };

function RecordForm({
    kind,
    record,
    onSaved,
    onCancel,
}: {
    kind: DirectoryKind;
    record: AccessRecord | null;
    onSaved: () => void;
    onCancel: () => void;
}) {
    const { data, setData, post, put, errors, processing } = useForm({ name: record?.name ?? '', guard_name: record?.guard_name ?? 'web' });
    const label = kind === 'roles' ? 'role' : 'permission';
    return (
        <form
            className="space-y-3 rounded-xl border p-4"
            onSubmit={(event) => {
                event.preventDefault();
                if (processing) return;
                const options = {
                    preserveScroll: true,
                    onSuccess: () => {
                        toast.success(`${label} saved.`);
                        onSaved();
                    },
                };
                if (record) put(`/${kind}/update/${record.id}`, options);
                else post(`/${kind}/store`, options);
            }}
        >
            <h2 className="font-semibold">
                {record ? 'Edit' : 'Add'} {label}
            </h2>
            <div className="space-y-1">
                <Label htmlFor="access-name">Name</Label>
                <Input
                    id="access-name"
                    required
                    maxLength={255}
                    autoFocus
                    value={data.name}
                    disabled={processing}
                    onChange={(event) => setData('name', event.target.value)}
                />
                <InputError message={errors.name} />
            </div>
            <div className="space-y-1">
                <Label htmlFor="access-guard">Guard</Label>
                <Input
                    id="access-guard"
                    required
                    maxLength={50}
                    value={data.guard_name}
                    disabled={processing}
                    onChange={(event) => setData('guard_name', event.target.value)}
                />
                <InputError message={errors.guard_name} />
            </div>
            <p className="text-muted-foreground text-xs">
                {kind === 'roles'
                    ? 'Classification comes from the modules of assigned permissions.'
                    : 'Use module followed by action, for example: facility hierarchy create.'}
            </p>
            <div className="flex gap-2">
                <Button disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
                <Button type="button" variant="outline" disabled={processing} onClick={onCancel}>
                    Cancel
                </Button>
            </div>
        </form>
    );
}

export default function AccessDirectory({ kind, canCreate, canEdit, canDelete, canView, canAssign = false }: Props) {
    const roles = kind === 'roles';
    const title = roles ? 'Roles' : 'Permissions';
    const [filters, setFilters] = useState(defaults);
    const [result, setResult] = useState(empty);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [revision, setRevision] = useState(0);
    const [editing, setEditing] = useState<AccessRecord | null>(null);
    const [formOpen, setFormOpen] = useState(false);
    const [deleting, setDeleting] = useState<number | null>(null);
    useEffect(() => {
        if (!canView) return;
        const controller = new AbortController();
        setLoading(true);
        setError('');
        const timer = window.setTimeout(() => {
            void loadAccessDirectory(kind, filters, controller.signal)
                .then((response) => {
                    if (filters.page > response.last_page) {
                        setFilters((current) => ({ ...current, page: response.last_page }));
                        return;
                    }
                    setResult(response);
                })
                .catch((cause: unknown) => {
                    if (!axios.isCancel(cause)) setError('Unable to load records. Please retry.');
                })
                .finally(() => {
                    if (!controller.signal.aborted) setLoading(false);
                });
        }, 300);
        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [canView, kind, filters, revision]);

    const change = (key: keyof DirectoryFilters, value: string | number) => setFilters((current) => ({ ...current, [key]: value, page: 1 }));
    const remove = async (record: AccessRecord) => {
        if (!canDelete || deleting !== null) return;
        const confirmation = await Swal.fire({
            title: `Delete ${record.name}?`,
            text: 'This removes the record from the directory.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
        });
        if (!confirmation.isConfirmed) return;
        setDeleting(record.id);
        try {
            await deleteAccessRecord(kind, record.id);
            setRevision((value) => value + 1);
            toast.success('Record deleted.');
        } catch (cause: unknown) {
            toast.error(axios.isAxiosError(cause) ? (cause.response?.data?.message ?? 'Unable to delete record.') : 'Unable to delete record.');
        } finally {
            setDeleting(null);
        }
    };
    const selectFilter = (key: 'module' | 'action' | 'guard' | 'assignment' | 'sort', label: string, options: { value: string; label: string }[]) => (
        <label className="space-y-1 text-xs">
            <span>{label}</span>
            <select
                className="border-input bg-background h-9 w-full rounded-md border px-2 text-sm"
                value={filters[key]}
                onChange={(event) => change(key, event.target.value)}
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
    return (
        <div className="space-y-4 p-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold">{title}</h1>
                    <p className="text-muted-foreground text-sm">
                        {roles ? 'Classified by assigned permission modules.' : 'Classified by module and action.'}
                    </p>
                </div>
                {canCreate && (
                    <Button
                        onClick={() => {
                            setEditing(null);
                            setFormOpen(true);
                        }}
                    >
                        Add {roles ? 'role' : 'permission'}
                    </Button>
                )}
            </div>
            {formOpen && (editing ? canEdit : canCreate) && (
                <RecordForm
                    key={editing?.id ?? 'new'}
                    kind={kind}
                    record={editing}
                    onCancel={() => setFormOpen(false)}
                    onSaved={() => {
                        setFormOpen(false);
                        setRevision((value) => value + 1);
                    }}
                />
            )}
            {canView && (
                <div className="overflow-hidden rounded-xl border">
                    <div className="bg-muted/20 grid gap-3 border-b p-3 sm:grid-cols-2 xl:grid-cols-4">
                        <label className="space-y-1 text-xs">
                            <span>Search</span>
                            <Input
                                placeholder={`Search ${title.toLowerCase()}...`}
                                maxLength={100}
                                value={filters.search}
                                onChange={(event) => change('search', event.target.value)}
                            />
                        </label>
                        {selectFilter('module', 'Classification / module', [
                            { value: '', label: 'All modules' },
                            ...result.filter_options.modules.map((value) => ({ value, label: value })),
                        ])}
                        {!roles &&
                            selectFilter('action', 'Action', [
                                { value: '', label: 'All actions' },
                                ...result.filter_options.actions.map((value) => ({ value, label: value })),
                            ])}
                        {selectFilter('guard', 'Guard', [
                            { value: '', label: 'All guards' },
                            ...result.filter_options.guards.map((value) => ({ value, label: value })),
                        ])}
                        {selectFilter('assignment', roles ? 'Permission configuration' : 'Role assignment', [
                            { value: '', label: 'All records' },
                            { value: 'assigned', label: roles ? 'With permissions' : 'Assigned to roles' },
                            { value: 'unassigned', label: roles ? 'Without permissions' : 'Not assigned to roles' },
                        ])}
                        {selectFilter('sort', 'Sort', [
                            { value: 'name', label: 'Name A–Z' },
                            { value: 'name_desc', label: 'Name Z–A' },
                            { value: 'newest', label: 'Newest first' },
                            { value: 'oldest', label: 'Oldest first' },
                        ])}
                        <div className="flex items-end">
                            <Button variant="outline" onClick={() => setFilters({ ...defaults })}>
                                Reset filters
                            </Button>
                        </div>
                    </div>
                    {error ? (
                        <div className="space-y-2 p-4" role="alert">
                            <p>{error}</p>
                            <Button variant="outline" onClick={() => setRevision((value) => value + 1)}>
                                Retry
                            </Button>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm whitespace-nowrap" aria-busy={loading}>
                                <thead className="bg-muted/30 border-b">
                                    <tr>
                                        {[
                                            'Name',
                                            'Guard',
                                            'Classification',
                                            ...(!roles ? ['Action'] : []),
                                            roles ? 'Permissions' : 'Roles',
                                            'Actions',
                                        ].map((heading) => (
                                            <th key={heading} className="px-3 py-2 font-medium">
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {loading ? (
                                        <tr>
                                            <td colSpan={roles ? 6 : 7} className="p-6 text-center">
                                                Loading...
                                            </td>
                                        </tr>
                                    ) : result.data.length ? (
                                        result.data.map((record) => (
                                            <tr key={record.id} className="hover:bg-muted/20">
                                                <td className="px-3 py-2 font-medium">
                                                    {record.name}
                                                    {record.endpoint && (
                                                        <div className="text-muted-foreground text-xs font-normal">{record.endpoint}</div>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">{record.guard_name}</td>
                                                <td className="px-3 py-2">
                                                    <div className="flex gap-1">
                                                        {record.classification.length ? (
                                                            record.classification.slice(0, 5).map((module) => (
                                                                <Badge key={module} variant="secondary">
                                                                    {module}
                                                                </Badge>
                                                            ))
                                                        ) : (
                                                            <Badge variant="outline">Unconfigured</Badge>
                                                        )}
                                                        {record.classification.length > 5 && (
                                                            <Badge
                                                                variant="outline"
                                                                title={record.classification.slice(5).join(', ')}
                                                                aria-label={`${record.classification.length - 5} more classifications: ${record.classification.slice(5).join(', ')}`}
                                                                tabIndex={0}
                                                            >
                                                                …
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                {!roles && <td className="px-3 py-2">{record.action || '—'}</td>}
                                                <td className="px-3 py-2">{record.assignment_count}</td>
                                                <td className="px-3 py-2">
                                                    <div className="flex gap-1">
                                                        {canEdit && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setEditing(record);
                                                                    setFormOpen(true);
                                                                }}
                                                            >
                                                                Edit
                                                            </Button>
                                                        )}
                                                        {canAssign && roles && (
                                                            <Button asChild size="sm" variant="outline">
                                                                <Link href={`/roles/assign/${record.id}`}>Assign permissions</Link>
                                                            </Button>
                                                        )}
                                                        {canDelete && (
                                                            <Button
                                                                size="sm"
                                                                variant="outline"
                                                                disabled={deleting !== null}
                                                                onClick={() => void remove(record)}
                                                            >
                                                                Delete
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={roles ? 6 : 7} className="p-6 text-center">
                                                No records match your filters.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    )}
                    <div className="flex flex-wrap items-center justify-between gap-3 border-t p-3 text-xs">
                        <span>{result.total} matching records</span>
                        <div className="flex items-center gap-2">
                            <label>
                                Rows{' '}
                                <select
                                    className="bg-background rounded border p-1"
                                    value={filters.per_page}
                                    onChange={(event) => change('per_page', Number(event.target.value))}
                                >
                                    {[10, 25, 50].map((size) => (
                                        <option key={size}>{size}</option>
                                    ))}
                                </select>
                            </label>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={loading || filters.page <= 1}
                                onClick={() => setFilters((current) => ({ ...current, page: current.page - 1 }))}
                            >
                                Previous
                            </Button>
                            <span>
                                Page {filters.page} of {result.last_page}
                            </span>
                            <Button
                                size="sm"
                                variant="outline"
                                disabled={loading || filters.page >= result.last_page}
                                onClick={() => setFilters((current) => ({ ...current, page: current.page + 1 }))}
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
