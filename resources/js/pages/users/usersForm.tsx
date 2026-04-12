import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { cn } from '@/lib/utils';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { AlertCircle, Check, ChevronsUpDown, Edit3, LoaderCircle, Plus, Save, ShieldCheck, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { type HospitalOption, type ProviderOption, type RegionOption, type RegisterForm, type UserRecord } from './types';

type AccessTypeValue = 'NONE' | 'EMR' | 'CHD' | 'HOSP';

type UserFormProps = {
    onUserCreated: () => void;
    onCancel: () => void;
    user: UserRecord | null;
    canCreate: boolean;
    canEdit: boolean;
};

const EMPTY_FORM: RegisterForm = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    access_id: '',
    access_type: '',
    status: true,
};

const resolveAccessType = (value?: string | null): AccessTypeValue => {
    if (value === 'EMR' || value === 'CHD' || value === 'HOSP') {
        return value;
    }

    return 'NONE';
};

const mapUserToForm = (user: UserRecord): RegisterForm => ({
    name: user.name ?? '',
    email: user.email ?? '',
    password: '',
    password_confirmation: '',
    access_id: user.access_id ?? '',
    access_type: (user.access_type ?? '') as RegisterForm['access_type'],
    status: user.status === 'A',
});

export default function UsersForm({ onUserCreated, onCancel, user, canCreate, canEdit }: UserFormProps) {
    const isEditing = Boolean(user?.id);
    const canSubmit = isEditing ? canEdit : canCreate;

    const nameInputRef = useRef<HTMLInputElement>(null);
    const [providers, setProviders] = useState<ProviderOption[]>([]);
    const [regions, setRegions] = useState<RegionOption[]>([]);
    const [hospitals, setHospitals] = useState<HospitalOption[]>([]);
    const [loadingReferences, setLoadingReferences] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    const [accessType, setAccessType] = useState<AccessTypeValue>('NONE');
    const [selectedProvider, setSelectedProvider] = useState('');
    const [selectedRegion, setSelectedRegion] = useState('');
    const [selectedHospital, setSelectedHospital] = useState('');

    const [providerOpen, setProviderOpen] = useState(false);
    const [regionOpen, setRegionOpen] = useState(false);
    const [hospitalOpen, setHospitalOpen] = useState(false);

    const { data, setData, errors, setError, clearErrors } = useForm<RegisterForm>(EMPTY_FORM);

    const accessLabel = useMemo(() => {
        switch (accessType) {
            case 'EMR':
                return 'Provider';
            case 'CHD':
                return 'Region';
            case 'HOSP':
                return 'Hospital';
            default:
                return 'General';
        }
    }, [accessType]);

    useEffect(() => {
        const loadReferences = async () => {
            setLoadingReferences(true);

            try {
                const [providersResponse, regionsResponse, hospitalsResponse] = await Promise.all([
                    axios.get('/emr/list'),
                    axios.get('/region/list'),
                    axios.get('/facilities-list'),
                ]);

                setProviders(Array.isArray(providersResponse.data) ? providersResponse.data : (providersResponse.data.data ?? []));
                setRegions(Array.isArray(regionsResponse.data) ? regionsResponse.data : (regionsResponse.data.data ?? []));
                setHospitals(Array.isArray(hospitalsResponse.data) ? hospitalsResponse.data : (hospitalsResponse.data.data ?? []));
            } catch (error) {
                console.error('Failed to load user access references:', error);
                toast.error('Unable to load access scope options right now.');
            } finally {
                setLoadingReferences(false);
            }
        };

        void loadReferences();
    }, []);

    useEffect(() => {
        const nextForm = user ? mapUserToForm(user) : EMPTY_FORM;
        const nextAccessType = resolveAccessType(user?.access_type);
        const nextAccessId = user?.access_id ?? '';

        setData(nextForm);
        clearErrors();
        setAccessType(nextAccessType);
        setSelectedProvider(nextAccessType === 'EMR' ? nextAccessId : '');
        setSelectedRegion(nextAccessType === 'CHD' ? nextAccessId : '');
        setSelectedHospital(nextAccessType === 'HOSP' ? nextAccessId : '');

        requestAnimationFrame(() => {
            nameInputRef.current?.focus();
        });
    }, [clearErrors, setData, user]);

    const resetToBlank = () => {
        setData(EMPTY_FORM);
        clearErrors();
        setAccessType('NONE');
        setSelectedProvider('');
        setSelectedRegion('');
        setSelectedHospital('');
    };

    const handleInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const { id, value } = event.target;
        setData(id as keyof RegisterForm, value);
    };

    const handleStatusChange = (checked: boolean) => {
        setData('status', checked);
    };

    const handleAccessTypeChange = (value: AccessTypeValue) => {
        setAccessType(value);
        clearErrors('access_id', 'access_type');
        setSelectedProvider('');
        setSelectedRegion('');
        setSelectedHospital('');
        setData('access_id', '');
        setData('access_type', value === 'NONE' ? '' : value);
    };

    const assignScopedAccess = (type: AccessTypeValue, value: string) => {
        if (type === 'EMR') {
            setSelectedProvider(value);
        }

        if (type === 'CHD') {
            setSelectedRegion(value);
        }

        if (type === 'HOSP') {
            setSelectedHospital(value);
        }

        setData('access_id', value);
        setData('access_type', type === 'NONE' ? '' : type);
        clearErrors('access_id', 'access_type');
    };

    const submit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!canSubmit) {
            return;
        }

        clearErrors();
        setIsSubmitting(true);

        const payload = {
            ...data,
            name: data.name.trim(),
            email: data.email.trim(),
            access_type: accessType === 'NONE' ? '' : accessType,
            access_id: accessType === 'NONE' ? '' : data.access_id,
            status: data.status,
        };

        try {
            if (isEditing && user) {
                await axios.put(route('user.update', user.id), payload);
            } else {
                await axios.post(route('user.store'), payload);
            }

            resetToBlank();
            onUserCreated();
            toast.success(isEditing ? 'User updated.' : 'User created.');
        } catch (error: any) {
            const fieldErrors = error.response?.data?.errors;

            if (fieldErrors) {
                Object.entries(fieldErrors).forEach(([field, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    setError(field as keyof RegisterForm, message as string);
                });
            }

            toast.error(error.response?.data?.message ?? 'Unable to save this user right now.');
            console.error(error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleCancel = () => {
        resetToBlank();
        onCancel();
    };

    const renderProviderSelector = () => (
        <div className="space-y-2">
            <Label htmlFor="provider-selector">Provider</Label>
            <Popover open={providerOpen} onOpenChange={setProviderOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="provider-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={providerOpen}
                        className="w-full justify-between"
                        disabled={!canSubmit || loadingReferences}
                    >
                        {providers.find((provider) => provider.emr_id === selectedProvider)?.emr_name || 'Select provider...'}
                        <ChevronsUpDown className="ml-2 size-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                    <Command>
                        <CommandInput placeholder="Search provider..." />
                        <CommandList>
                            <CommandEmpty>No provider found.</CommandEmpty>
                            <CommandGroup>
                                {providers.map((provider) => (
                                    <CommandItem
                                        key={provider.emr_id}
                                        value={provider.emr_name}
                                        onSelect={() => {
                                            assignScopedAccess('EMR', provider.emr_id);
                                            setProviderOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 size-4', selectedProvider === provider.emr_id ? 'opacity-100' : 'opacity-0')} />
                                        {provider.emr_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.access_id} />
        </div>
    );

    const renderRegionSelector = () => (
        <div className="space-y-2">
            <Label htmlFor="region-selector">Region</Label>
            <Popover open={regionOpen} onOpenChange={setRegionOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="region-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={regionOpen}
                        className="w-full justify-between"
                        disabled={!canSubmit || loadingReferences}
                    >
                        {regions.find((region) => region.regcode === selectedRegion)?.regname || 'Select region...'}
                        <ChevronsUpDown className="ml-2 size-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                    <Command>
                        <CommandInput placeholder="Search region..." />
                        <CommandList>
                            <CommandEmpty>No region found.</CommandEmpty>
                            <CommandGroup>
                                {regions.map((region) => (
                                    <CommandItem
                                        key={region.regcode}
                                        value={region.regname}
                                        onSelect={() => {
                                            assignScopedAccess('CHD', region.regcode);
                                            setRegionOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 size-4', selectedRegion === region.regcode ? 'opacity-100' : 'opacity-0')} />
                                        {region.regname}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.access_id} />
        </div>
    );

    const renderHospitalSelector = () => (
        <div className="space-y-2">
            <Label htmlFor="hospital-selector">Hospital</Label>
            <Popover open={hospitalOpen} onOpenChange={setHospitalOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="hospital-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={hospitalOpen}
                        className="w-full justify-between"
                        disabled={!canSubmit || loadingReferences}
                    >
                        {hospitals.find((hospital) => hospital.hfhudcode === selectedHospital)?.facility_name || 'Select hospital...'}
                        <ChevronsUpDown className="ml-2 size-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                    <Command>
                        <CommandInput placeholder="Search hospital..." />
                        <CommandList>
                            <CommandEmpty>No hospital found.</CommandEmpty>
                            <CommandGroup>
                                {hospitals.map((hospital) => (
                                    <CommandItem
                                        key={hospital.hfhudcode}
                                        value={hospital.facility_name}
                                        onSelect={() => {
                                            assignScopedAccess('HOSP', hospital.hfhudcode);
                                            setHospitalOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 size-4', selectedHospital === hospital.hfhudcode ? 'opacity-100' : 'opacity-0')} />
                                        {hospital.facility_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.access_id} />
        </div>
    );

    if (!canCreate && !isEditing) {
        return (
            <Card className="overflow-hidden">
                <CardHeader className="bg-muted/30 border-b">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <Edit3 className="size-4" />
                        Edit User
                    </CardTitle>
                    <CardDescription>Select a user from the list to load it into the editor.</CardDescription>
                </CardHeader>
                <CardContent className="p-6">
                    <p className="text-muted-foreground text-sm">
                        This account can edit existing users but cannot create new ones, so the form stays empty until you choose a record to edit.
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
                            {isEditing ? 'Edit User' : 'Add User'}
                        </CardTitle>
                        <CardDescription>
                            {isEditing
                                ? 'Update account details, access scope, or activation status.'
                                : 'Create a new account and choose the access scope it should inherit.'}
                        </CardDescription>
                    </div>

                    <div className="flex flex-col items-end gap-2">
                        <Badge variant={data.status ? 'default' : 'outline'}>{data.status ? 'Active' : 'Inactive'}</Badge>
                        <Badge variant="outline">{accessLabel}</Badge>
                    </div>
                </div>

                {isEditing && user ? (
                    <p className="text-muted-foreground text-xs">
                        User #{user.id}
                        {user.primary_role ? ` - ${user.primary_role}` : ''}
                    </p>
                ) : null}
            </CardHeader>

            <form onSubmit={submit}>
                <CardContent className="space-y-5 p-6">
                    <div className="space-y-2">
                        <Label htmlFor="name">Full Name</Label>
                        <Input
                            id="name"
                            ref={nameInputRef}
                            value={data.name}
                            onChange={handleInputChange}
                            placeholder="Enter full name"
                            autoComplete="off"
                            disabled={!canSubmit || isSubmitting}
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="email">Email Address</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={handleInputChange}
                            placeholder="name@example.com"
                            autoComplete="off"
                            disabled={!canSubmit || isSubmitting}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="password">Password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={data.password}
                                onChange={handleInputChange}
                                placeholder={isEditing ? 'Leave blank to keep current password' : 'Create password'}
                                autoComplete="new-password"
                                disabled={!canSubmit || isSubmitting}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirm Password</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={handleInputChange}
                                placeholder={isEditing ? 'Repeat new password if changing it' : 'Confirm password'}
                                autoComplete="new-password"
                                disabled={!canSubmit || isSubmitting}
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>
                    </div>

                    <div className="bg-muted/20 space-y-3 rounded-lg border p-4">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <Label htmlFor="status">Account Status</Label>
                                <p className="text-muted-foreground text-xs">
                                    Inactive users remain in history but cannot sign in until reactivated.
                                </p>
                            </div>

                            <Switch id="status" checked={data.status} onCheckedChange={handleStatusChange} disabled={!canSubmit || isSubmitting} />
                        </div>
                        <InputError message={errors.status} />
                    </div>

                    <div className="space-y-3">
                        <div className="space-y-2">
                            <Label htmlFor="access_type">Access Scope</Label>
                            <Select value={accessType} onValueChange={(value: AccessTypeValue) => handleAccessTypeChange(value)}>
                                <SelectTrigger disabled={!canSubmit || isSubmitting}>
                                    <SelectValue placeholder="Select access scope" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="NONE">General Access</SelectItem>
                                    <SelectItem value="EMR">EMR Provider</SelectItem>
                                    <SelectItem value="CHD">Region</SelectItem>
                                    <SelectItem value="HOSP">Hospital</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.access_type} />
                        </div>

                        {accessType === 'NONE' ? (
                            <Alert>
                                <ShieldCheck className="size-4" />
                                <AlertTitle>General Access</AlertTitle>
                                <AlertDescription>This account is not scoped to a specific provider, region, or hospital.</AlertDescription>
                            </Alert>
                        ) : null}

                        {accessType === 'EMR' && renderProviderSelector()}
                        {accessType === 'CHD' && renderRegionSelector()}
                        {accessType === 'HOSP' && renderHospitalSelector()}

                        {loadingReferences && accessType !== 'NONE' ? (
                            <Alert>
                                <AlertCircle className="size-4" />
                                <AlertTitle>Loading reference data</AlertTitle>
                                <AlertDescription>Provider, region, and hospital options are still loading.</AlertDescription>
                            </Alert>
                        ) : null}
                    </div>
                </CardContent>

                <CardFooter className="bg-muted/10 flex items-center justify-between gap-3 border-t px-6 py-4">
                    <Button type="submit" disabled={!canSubmit || isSubmitting}>
                        {isSubmitting ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                        {isSubmitting ? 'Saving...' : isEditing ? 'Save Changes' : 'Create User'}
                    </Button>

                    {(isEditing || canCreate) && (
                        <Button type="button" variant="outline" onClick={handleCancel} disabled={isSubmitting}>
                            <X className="size-4" />
                            {isEditing ? 'Cancel Edit' : 'Clear Form'}
                        </Button>
                    )}
                </CardFooter>
            </form>
        </Card>
    );
}
