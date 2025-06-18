import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Head, useForm } from '@inertiajs/react';
import { Check, ChevronsUpDown, Hospital, LoaderCircle, Save, X, Edit } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import DemographicSelector from '../Demographics/Demographics_selector';
import axios from 'axios';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type Props = {
    onCreated: () => void;
    onCancel: () => void;
    formval?: any;
    canCreate: boolean;
};

type Formtype = {
    hfhudcode: string;
    facility_name: string;
    fhudaddress: string;
    factype_code: string;
    region: string;
    province: string;
    city: string;
    barangay: string;
    status: boolean;
};

export default function FacilityForm({ onCreated, onCancel, formval, canCreate }: Props) {
    const nameInputRef = useRef<HTMLInputElement>(null);

    const [factypes, setFacilitytype] = useState([]);
    const [value, setValue] = useState(formval?.factype_code || '');
    const [open, setOpen] = useState(false);

    const { data, setData, post, put, processing, errors, reset } = useForm<Formtype>({
        hfhudcode: formval?.hfhudcode || '',
        facility_name: formval?.facility_name || '',
        fhudaddress: formval?.fhudaddress || '',
        factype_code: formval?.factype_code || '',
        region: formval?.region || '',
        province: formval?.province || '',
        city: formval?.city || '',
        barangay: formval?.barangay || '',
        status: formval?.status ?? true,
    });

    useEffect(() => {
        axios.get('/facility_type/list')
            .then((response) => {
                setFacilitytype(response.data);
            })
            .catch((error) => {
                console.error('Error fetching facility types:', error);
            });
    }, []);

    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    useEffect(() => {
        if (formval) {
            setData({
                hfhudcode: formval.hfhudcode || '',
                facility_name: formval.facility_name || '',
                fhudaddress: formval.fhudaddress || '',
                factype_code: formval.factype_code || '',
                region: formval.region || '',
                province: formval.province || '',
                city: formval.city || '',
                barangay: formval.barangay || '',
                status: formval.status ?? true,
            });
            setValue(formval.factype_code || '');
        } else {
            reset();
        }
    }, [formval]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setData(e.target.id, e.target.value);
    };

    const handleSwitchChange = (checked: boolean) => {
        setData('status', checked);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (formval) {
            put(route('facility.update', formval.id), {
                onSuccess: () => {
                    reset();
                    onCreated();
                    toast.success('Facility updated!');
                },
            });
        } else {
            post(route('facility.store'), {
                onSuccess: () => {
                    reset();
                    onCreated();
                    toast.success('Facility created!');
                },
            });
        }
    };

    useEffect(() => {

        axios.get('/facilities-list')
            .then(res => setHospital(res.data.data))
            .catch(err => console.error(err));

        nameInputRef.current?.focus();
    }, []);

    return (
        <div className="mt-2 mr-3 ml-2 w-full">
            <Head title={formval ? 'Edit Facility' : 'Register Facility'} />
            <div className="mb-2 flex items-center">
                {formval ? <Edit size={18} /> : <Hospital size={18} />}
                <h1 className="ml-2 text-lg font-semibold text-gray-800">
                    {formval ? 'Edit Facility' : 'Create Facility'}
                </h1>
            </div>
            <HeadingSmall title="Facility Information" description="Enter your details below." />
            <form className="mt-4 flex flex-col gap-4" onSubmit={submit}>
                <div className="grid gap-1">
                    <Label htmlFor="hfhudcode">Code:</Label>
                    <Input
                        id="hfhudcode"
                        ref={nameInputRef}
                        value={data.hfhudcode}
                        placeholder="Facility code"
                        onChange={handleChange}
                        className="mt-1 block h-8 w-full px-2 py-1 text-xs"
                        autoComplete="off"
                        disabled={!canCreate}
                    />
                    <InputError message={errors.hfhudcode} className="mt-1 text-xs" />

                    <Label htmlFor="facility_name">Facility name:</Label>
                    <Input
                        id="facility_name"
                        value={data.facility_name}
                        placeholder="Facility name"
                        onChange={handleChange}
                        className="mt-1 block h-8 w-full px-2 py-1 text-sm"
                        autoComplete="off"
                        disabled={!canCreate}
                    />
                    <InputError message={errors.facility_name} className="mt-1 text-xs" />

                    <div className="mb-2 flex items-center space-x-4">
                        <Label htmlFor="status">Status:</Label>
                        <Switch
                            id="status"
                            checked={data.status}
                            onCheckedChange={handleSwitchChange}
                            disabled={!canCreate}
                        />
                    </div>
                    <InputError message={errors.status} className="mt-1 text-xs" />

                    <Label htmlFor="provider-selector">Facility type</Label>
                    <input type="hidden" name="fac_code" id="fac_code" value={value || ''} />

                    <Popover open={open} onOpenChange={setOpen}>
                        <PopoverTrigger asChild>
                            <Button
                                id="provider-selector"
                                variant="outline"
                                role="combobox"
                                aria-expanded={open}
                                className="w-full justify-between"
                                disabled={!canCreate}
                            >
                                {value ? factypes.find((f) => f.factype_code === value)?.description : 'Select Facility type...'}
                                <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent className="w-100 p-0" id="provider-popover-content">
                            <Command>
                                <CommandInput placeholder="Search Facility type..." />
                                <CommandList>
                                    <CommandEmpty>Not found.</CommandEmpty>
                                    <CommandGroup>
                                        {factypes.map((factype) => (
                                            <CommandItem
                                                key={factype.factype_code}
                                                value={factype.description}
                                                onSelect={() => {
                                                    setValue(factype.factype_code);
                                                    setData('factype_code', factype.factype_code);
                                                    setOpen(false);
                                                }}
                                            >
                                                <Check className={cn('mr-2 h-4 w-4', value === factype.factype_code ? 'opacity-100' : 'opacity-0')} />
                                                {factype.description}
                                            </CommandItem>
                                        ))}
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                        {errors.factype_code && <div className="mt-1 text-xs text-red-500">{errors.factype_code}</div>}
                    </Popover>

                    <p>Demographics</p>

                    <Label htmlFor="fhudaddress">Address:</Label>
                    <Textarea
                        id="fhudaddress"
                        value={data.fhudaddress}
                        onChange={handleChange}
                        className="mt-1 block w-full px-2 py-1 text-sm"
                        placeholder="Address"
                        autoComplete="off"
                        disabled={!canCreate}
                    />
                    <InputError message={errors.fhudaddress} className="mt-1 text-xs" />

                    <DemographicSelector
                        variant="vertical"
                        value={{
                            region: data.region,
                            province: data.province,
                            city: data.city,
                            barangay: data.barangay,
                        }}
                        onChange={(val) => {
                            setData('region', val.region || '');
                            setData('province', val.province || '');
                            setData('city', val.city || '');
                            setData('barangay', val.barangay || '');
                        }}
                        canCreate={canCreate}
                    />

                    <div className="mt-4 flex justify-between gap-4">
                        <Button
                            type="submit"
                            className="flex flex-1 items-center justify-center gap-2 rounded-md border border-green-600 bg-white py-2 font-semibold text-green-600 transition-all hover:bg-green-600 hover:text-white"
                            disabled={processing || !canCreate}
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            {processing ? 'Processing...' : <><Save size={12} /></>}
                        </Button>

                        {formval && (
                            <Button
                                type="button"
                                onClick={onCancel}
                                className="flex flex-1 items-center justify-center gap-2 rounded-md border border-red-400 bg-white py-2 font-semibold text-red-600 transition-all hover:bg-red-600 hover:text-white"
                                disabled={!canCreate}
                            >
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                {processing ? 'Processing...' : <><X size={12} /></>}
                            </Button>
                        )}
                    </div>
                </div>
            </form>
        </div>
    );
}
