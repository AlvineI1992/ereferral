import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';
import { Edit3, LoaderCircle, Plus, Save, X } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { type EmrRecord } from './types';

type Props = {
    onCreated: () => void;
    onCancel: () => void;
    emr: EmrRecord | null;
    canCreate: boolean;
    canEdit: boolean;
};

type FormValues = {
    emr_name: string;
    status: boolean;
    remarks: string;
};

const EMPTY_FORM: FormValues = {
    emr_name: '',
    status: true,
    remarks: '',
};

const mapEmrToForm = (emr: EmrRecord): FormValues => ({
    emr_name: emr.emr_name ?? '',
    status: Number(emr.status) === 1 || emr.status === true || emr.status === '1',
    remarks: emr.remarks ?? '',
});

export default function Form({ onCreated, onCancel, emr, canCreate, canEdit }: Props) {
    const isEditing = Boolean(emr?.emr_id);
    const canSubmit = isEditing ? canEdit : canCreate;
    const nameInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, put, processing, errors, clearErrors, transform } = useForm<FormValues>(EMPTY_FORM);

    useEffect(() => {
        setData(emr ? mapEmrToForm(emr) : EMPTY_FORM);
        clearErrors();

        requestAnimationFrame(() => {
            nameInputRef.current?.focus();
        });
    }, [clearErrors, emr, setData]);

    const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { id, value } = event.target;
        setData(id as keyof FormValues, value);
    };

    const handleSwitchChange = (checked: boolean) => {
        setData('status', checked);
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        transform((current) => ({
            ...current,
            emr_name: current.emr_name.trim(),
            remarks: current.remarks.trim(),
            status: current.status,
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setData(EMPTY_FORM);
                clearErrors();
                onCreated();
                toast.success(isEditing ? 'Provider updated.' : 'Provider created.');
            },
            onError: () => {
                toast.error('Please review the provider form and try again.');
            },
        };

        if (isEditing && emr) {
            put(route('emr.update', emr.emr_id), options);
            return;
        }

        post(route('emr.store'), options);
    };

    const handleCancel = () => {
        setData(EMPTY_FORM);
        clearErrors();
        onCancel();
    };

    if (!canCreate && !isEditing) {
        return (
            <Card className="overflow-hidden">
                <CardHeader className="bg-muted/30 border-b">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Edit3 className="size-4" />
                        Edit Provider
                    </CardTitle>
                    <CardDescription>Select a provider from the list to load it into the editor.</CardDescription>
                </CardHeader>
                <CardContent className="p-6">
                    <p className="text-muted-foreground text-sm">
                        This account can edit existing providers but cannot create new ones, so the form stays empty until you choose a record to
                        edit.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 border-b">
                <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                        <CardTitle className="flex items-center gap-2 text-base">
                            {isEditing ? <Edit3 className="size-4" /> : <Plus className="size-4" />}
                            {isEditing ? 'Edit Provider' : 'Add Provider'}
                        </CardTitle>
                        <CardDescription>
                            {isEditing
                                ? 'Update provider details without changing its facility assignments.'
                                : 'Create a provider record that can be assigned to one or more facilities.'}
                        </CardDescription>
                    </div>

                    <Badge variant={data.status ? 'default' : 'outline'}>{data.status ? 'Active' : 'Inactive'}</Badge>
                </div>

                {isEditing && emr ? (
                    <p className="text-muted-foreground text-xs">
                        Provider #{emr.emr_id}
                        {typeof emr.assigned_facilities_count === 'number'
                            ? ` - ${emr.assigned_facilities_count} assigned facilit${emr.assigned_facilities_count === 1 ? 'y' : 'ies'}`
                            : ''}
                    </p>
                ) : null}
            </CardHeader>

            <form onSubmit={submit}>
                <CardContent className="space-y-5 p-6">
                    <div className="space-y-2">
                        <Label htmlFor="emr_name">Provider Name</Label>
                        <Input
                            id="emr_name"
                            ref={nameInputRef}
                            value={data.emr_name}
                            onChange={handleChange}
                            placeholder="Enter provider name"
                            autoComplete="off"
                            disabled={!canSubmit || processing}
                        />
                        <InputError message={errors.emr_name} />
                    </div>

                    <div className="bg-muted/20 space-y-3 rounded-lg border p-4">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <p className="text-muted-foreground text-xs">
                                    Inactive providers stay in history but cannot be assigned to new facilities.
                                </p>
                            </div>

                            <Switch id="status" checked={data.status} onCheckedChange={handleSwitchChange} disabled={!canSubmit || processing} />
                        </div>
                        <InputError message={errors.status} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="remarks">Remarks</Label>
                        <Textarea
                            id="remarks"
                            value={data.remarks}
                            onChange={handleChange}
                            className="min-h-28"
                            placeholder="Add a short note for admins or assignment context"
                            autoComplete="off"
                            disabled={!canSubmit || processing}
                        />
                        <InputError message={errors.remarks} />
                    </div>
                </CardContent>

                <CardFooter className="bg-muted/10 flex items-center justify-between gap-3 border-t px-6 py-4">
                    <Button type="submit" disabled={!canSubmit || processing}>
                        {processing ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                        {processing ? 'Saving...' : isEditing ? 'Save Changes' : 'Create Provider'}
                    </Button>

                    {(isEditing || canCreate) && (
                        <Button type="button" variant="outline" onClick={handleCancel} disabled={processing}>
                            <X className="size-4" />
                            {isEditing ? 'Cancel Edit' : 'Clear Form'}
                        </Button>
                    )}
                </CardFooter>
            </form>
        </Card>
    );
}
