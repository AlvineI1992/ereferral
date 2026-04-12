import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';
import { LoaderCircle, Plus, Save, ScrollText, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { type ReligionRecord } from './types';

type Props = {
    onCreated: () => void;
    onCancel?: () => void;
    formval?: ReligionRecord | null;
};

type FormType = {
    relcode: string;
    reldesc: string;
    relstat: boolean;
};

export default function ReligionForm({ onCreated, onCancel, formval }: Props) {
    const isEditMode = Boolean(formval?.relcode);
    const codeInputRef = useRef<HTMLInputElement>(null);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);
    const { data, setData, reset } = useForm<FormType>({
        relcode: formval?.relcode ?? '',
        reldesc: formval?.reldesc ?? '',
        relstat: formval ? formval.relstat === 'A' : true,
    });

    useEffect(() => {
        codeInputRef.current?.focus();
    }, []);

    useEffect(() => {
        reset({
            relcode: formval?.relcode ?? '',
            reldesc: formval?.reldesc ?? '',
            relstat: formval ? formval.relstat === 'A' : true,
        });
        setFieldErrors({});
    }, [formval, reset]);

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const payload = {
            relcode: data.relcode.trim().toUpperCase(),
            reldesc: data.reldesc.trim(),
            relstat: data.relstat ? 'A' : 'I',
        };

        try {
            setSaving(true);
            setFieldErrors({});

            if (isEditMode) {
                await axios.put(`/religions/update/${formval!.relcode}`, {
                    reldesc: payload.reldesc,
                    relstat: payload.relstat,
                });
                toast.success('Religion updated.');
            } else {
                await axios.post('/religions/store', payload);
                toast.success('Religion created.');
            }

            reset({
                relcode: '',
                reldesc: '',
                relstat: true,
            });
            onCreated();
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const validationErrors = error.response.data.errors ?? {};
                const normalizedErrors = Object.fromEntries(
                    Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
                ) as Record<string, string>;
                setFieldErrors(normalizedErrors);
                toast.error('Please check the religion form.');

                return;
            }

            toast.error('Unable to save religion right now.');
        } finally {
            setSaving(false);
        }
    };

    const handleCancel = () => {
        reset({
            relcode: '',
            reldesc: '',
            relstat: true,
        });
        setFieldErrors({});
        onCancel?.();
    };

    return (
        <div className="w-full">
            <Head title={isEditMode ? 'Edit Religion' : 'Create Religion'} />
            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                <div className="mb-4 flex items-center gap-2">
                    {isEditMode ? <ScrollText className="size-5" /> : <Plus className="size-5" />}
                    <h2 className="text-lg font-semibold">{isEditMode ? 'Edit Religion' : 'Create Religion'}</h2>
                </div>

                <HeadingSmall
                    title="Religion Reference"
                    description="Maintain religion codes used by the referral form and the `/api/religions` reference endpoint."
                />

                <form className="mt-5 space-y-4" onSubmit={handleSubmit}>
                    <div className="space-y-2">
                        <Label htmlFor="relcode">Religion code</Label>
                        <Input
                            id="relcode"
                            ref={codeInputRef}
                            value={data.relcode}
                            onChange={(event) => {
                                setData('relcode', event.target.value.toUpperCase().replace(/\s+/g, '_'));
                                setFieldErrors((current) => ({ ...current, relcode: '' }));
                            }}
                            placeholder="ROMAN_CATHOLIC"
                            disabled={isEditMode}
                            autoComplete="off"
                        />
                        <p className="text-muted-foreground text-xs">Use uppercase letters, numbers, underscore, or dash.</p>
                        <InputError message={fieldErrors.relcode} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="reldesc">Religion description</Label>
                        <Input
                            id="reldesc"
                            value={data.reldesc}
                            onChange={(event) => {
                                setData('reldesc', event.target.value);
                                setFieldErrors((current) => ({ ...current, reldesc: '' }));
                            }}
                            placeholder="Roman Catholic"
                            autoComplete="off"
                        />
                        <InputError message={fieldErrors.reldesc} />
                    </div>

                    <div className="flex items-center justify-between rounded-xl border px-4 py-3">
                        <div>
                            <p className="text-sm font-medium">Active status</p>
                            <p className="text-muted-foreground text-xs">
                                Active religions are returned by `/api/religions` by default.
                            </p>
                        </div>
                        <Switch
                            checked={data.relstat}
                            onCheckedChange={(checked) => {
                                setData('relstat', checked);
                                setFieldErrors((current) => ({ ...current, relstat: '' }));
                            }}
                        />
                    </div>
                    <InputError message={fieldErrors.relstat} />

                    <div className="flex gap-3">
                        <Button type="submit" className="flex-1" disabled={saving}>
                            {saving ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                            {isEditMode ? 'Save changes' : 'Create religion'}
                        </Button>

                        {isEditMode && (
                            <Button type="button" variant="outline" className="flex-1" onClick={handleCancel} disabled={saving}>
                                <X className="size-4" />
                                Cancel
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
}
