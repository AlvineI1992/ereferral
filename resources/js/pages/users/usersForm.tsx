import * as React from 'react';
import {
    FormEventHandler,
    useEffect,
    useRef,
    useState,
} from 'react';
import { Head, useForm } from '@inertiajs/react';
import axios from 'axios';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import {
    Check,
    ChevronsUpDown,
    Edit,
    LoaderCircle,
    Save,
    User,
    X,
} from 'lucide-react';
import { RegisterForm } from './types';
import toastr from "toastr";

// -------------------- Types --------------------
type Framework = {
    emr_id: string;
    emr_name: string;
};

type Region = {
    regcode: string;
    regname: string;
};

type Hospital = {
    hfhudcode: string;
    facility_name: string;
};

type UserFormProps = {
    onUserCreated: () => void;
    onCancel: () => void;
    user?: any;
};
type Provider = { emr_id: number; emr_name: string };

type Props = {
  providers?: Provider[];
};



// -------------------- Component --------------------
export default function UsersForm({ onUserCreated, onCancel, user }: UserFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: user?.name || '',
        email: user?.email || '',
        password: user?.password || '',
        password_confirmation: user?.password_confirmation || '',
        access_id: user?.access_id || '',
        access_type: user?.access_type || ''
    });

    const nameInputRef = useRef<HTMLInputElement>(null);
    const [providers, setProvider] = useState([]);
    const [regions, setRegions] = useState([]);
    const [hospitals, setHospital] = useState([]);

    const [selectedProvider, setSelectedProvider] = useState(data.access_id);
    const [selectedRegion, setSelectedRegion] = useState(data.access_id);
    const [selectedHospital, setSelectedHospital] = useState(data.access_id);

    const [popoverOpen, setPopoverOpen] = useState(false);
    const [regionPopoverOpen, setRegionPopoverOpen] = useState(false);
    const [hospitalPopoverOpen, setHospitalPopoverOpen] = useState(false);

    const [accessType, setAccessType] = useState(data.access_type);

    useEffect(() => {
        if (user) {
            setData({
                name: user.name,
                email: user.email,
                password: '',
                password_confirmation: '',
                access_id: user.access_id,
                access_type: user.access_type,
            });
            setAccessType(user.access_type); 
            if (user.access_type === 'EMR') {
                setSelectedProvider(user.access_id);
            } else if (user.access_type === 'CHD') {
                setSelectedRegion(user.access_id);
            } else if (user.access_type === 'HOSP') {
                setSelectedHospital(user.access_id);
            }
        } else {
            reset();
        }
    }, [user]);

    useEffect(() => {
        axios.get('/emr/list')
            .then(res => setProvider(res.data))
            .catch(err => console.error(err));

        axios.get('/region/list')
            .then(res => {
                const regionList = Array.isArray(res.data) ? res.data : res.data.data || [];
                setRegions(regionList);
            })
            .catch(err => {
                console.error('Failed to load regions:', err);
                setRegions([]);
            });

        axios.get('/facilities-list/table')
            .then(res => setHospital(res.data.data))
            .catch(err => console.error(err));

        nameInputRef.current?.focus();
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(e.target.id, e.target.value);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
    
        if (user) {
            axios.put(route("user.update", user.id), data)
                .then(() => {
                    reset();
                    setSelectedProvider('');
                    setSelectedRegion('');
                    setSelectedHospital('');
                    onUserCreated();
                    toastr.success("User updated!", "Success");
                })
                .catch(error => {
                    toastr.error("Failed to update user", "Error");
                    console.error(error);
                });
        } else {
            axios.post(route("user.store"), data)
                .then(() => {
                    reset();
                    setSelectedProvider('');
                    setSelectedRegion('');
                    setSelectedHospital('');
                    onUserCreated();
                    toastr.success("User created!", "Success");
                })
                .catch(error => {
                    toastr.error(error, "Error");
                });
        }
    };

    const renderProviderSelector = (label: string) => (
        <div className="grid gap-1">
            <Label htmlFor="provider-selector">{label}</Label>
            <Popover open={popoverOpen} onOpenChange={setPopoverOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="provider-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={popoverOpen}
                        className="w-full justify-between"
                    >
                        {(providers ?? []).find(f => f.emr_id === selectedProvider)?.emr_name || 'Select provider...'}
                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-100 p-0">
                    <Command>
                        <CommandInput placeholder="Search provider..." />
                        <CommandList>
                            <CommandEmpty>No provider found.</CommandEmpty>
                            <CommandGroup>
                                {providers.map(provider => (
                                    <CommandItem
                                        key={provider.emr_id}
                                        value={provider.emr_name}
                                        onSelect={() => {
                                            setSelectedProvider(provider.emr_id);
                                            setData('access_id', provider.emr_id);
                                            setPopoverOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 h-4 w-4', selectedProvider === provider.emr_id ? 'opacity-100' : 'opacity-0')} />
                                        {provider.emr_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.emr_id} />
        </div>
    );

    const renderRegionSelector = (label: string) => (
        <div className="grid gap-1">
            <Label htmlFor="region-selector">{label}</Label>
            <Popover open={regionPopoverOpen} onOpenChange={setRegionPopoverOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="region-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={regionPopoverOpen}
                        className="w-full justify-between"
                    >
                        {regions.find(f => f.regcode === selectedRegion)?.regname || 'Select region...'}
                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-100 p-0">
                    <Command>
                        <CommandInput placeholder="Search region..." />
                        <CommandList>
                            <CommandEmpty>No region found.</CommandEmpty>
                            <CommandGroup>
                                {regions.map(region => (
                                    <CommandItem
                                        key={region.regcode}
                                        value={region.regname}
                                        onSelect={() => {
                                            setSelectedRegion(region.regcode);
                                            setData('access_id', region.regcode);
                                            setRegionPopoverOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 h-4 w-4', selectedRegion === region.regcode ? 'opacity-100' : 'opacity-0')} />
                                        {region.regname}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.regcode} />
        </div>
    );

    const renderHospitalSelector = (label: string) => (
        <div className="grid gap-1">
            <Label htmlFor="hospital-selector">{label}</Label>
            <Popover open={hospitalPopoverOpen} onOpenChange={setHospitalPopoverOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="hospital-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={hospitalPopoverOpen}
                        className="w-full justify-between"
                    >
                        {hospitals.find(f => f.hfhudcode === selectedHospital)?.facility_name || 'Select hospital...'}
                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-100 p-0">
                    <Command>
                        <CommandInput placeholder="Search hospital..." />
                        <CommandList>
                            <CommandEmpty>No hospital found.</CommandEmpty>
                            <CommandGroup>
                                {hospitals.map(hospital => (
                                    <CommandItem
                                        key={hospital.hfhudcode}
                                        value={hospital.facility_name}
                                        onSelect={() => {
                                            setSelectedHospital(hospital.hfhudcode);
                                            setData('access_id', hospital.hfhudcode);
                                            setHospitalPopoverOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 h-4 w-4', selectedHospital === hospital.hfhudcode ? 'opacity-100' : 'opacity-0')} />
                                        {hospital.facility_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
            <InputError message={errors.hfhudcode} />
        </div>
    );

    return (
        <div className="mt-2 mr-3 ml-2 w-full">
            <Head title="Register" />
            <div className="mb-2 flex items-center">
                {user ? <Edit size={18} /> : <User size={18} />}
                <h1 className="ml-2 text-lg font-semibold">{user ? 'Edit User' : 'Create User'}</h1>
            </div>
            <HeadingSmall
                title="Profile information"
                description="Enter your details below to create your account"
            />
            <form className="mt-2 flex flex-col gap-2" onSubmit={submit}>
                <Label htmlFor="name">Name</Label>
                <Input
                    ref={nameInputRef}
                    id="name"
                    value={data.name}
                    onChange={handleChange}
                    disabled={processing}
                    placeholder="Full name"
                />
                <InputError message={errors.name} />

                <Label htmlFor="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    value={data.email}
                    onChange={handleChange}
                    disabled={processing}
                    placeholder="Email"
                />
                <InputError message={errors.email} />

                <Label htmlFor="password">Password</Label>
                <Input
                    id="password"
                    type="password"
                    value={data.password}
                    onChange={handleChange}
                    disabled={processing}
                    placeholder="Password"
                />
                <InputError message={errors.password} />

                <Label htmlFor="password_confirmation">Confirm Password</Label>
                <Input
                    id="password_confirmation"
                    type="password"
                    value={data.password_confirmation}
                    onChange={handleChange}
                    disabled={processing}
                    placeholder="Confirm Password"
                />
                <InputError message={errors.password_confirmation} />

                <p className="text-md font-semibold">Access Type</p>
                <Select
                    value={accessType}
                    onValueChange={value => {
                        setAccessType(value);
                        setData('access_type', value);
                    }}
                >
                    <SelectTrigger>
                        <SelectValue placeholder="Select access type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="EMR">EMR Provider</SelectItem>
                        <SelectItem value="HOSP">Hospital</SelectItem>
                        <SelectItem value="CHD">Region</SelectItem>
                    </SelectContent>
                </Select>

                {accessType === 'EMR' && renderProviderSelector('EMR Provider')}
                {accessType === 'CHD' && renderRegionSelector('Region')}
                {accessType === 'HOSP' && renderHospitalSelector('Hospital')}

                <Input
                    type="hidden"
                    id="access_id"
                    value={
                        accessType === 'CHD'
                            ? selectedRegion
                            : accessType === 'HOSP'
                                ? selectedHospital
                                : selectedProvider
                    }
                />

                <div className="mt-4 flex gap-2">
                    <Button type="submit" disabled={processing} className="flex-1">
                        {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : <Save size={16} />}
                        <span>{processing ? 'Processing...' : 'Save'}</span>
                    </Button>
                    {user && (
                        <Button
                            type="button"
                            onClick={onCancel}
                            variant="outline"
                            className="flex-1 text-red-600 border-red-400 hover:bg-red-600 hover:text-white"
                        >
                            <X size={16} />
                            <span>Cancel</span>
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
