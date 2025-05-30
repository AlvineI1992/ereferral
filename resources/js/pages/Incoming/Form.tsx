import { useRef, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';

import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

import { Map, User } from 'lucide-react';

import DemographicSelector from '../Demographics/Demographics_selector';

import ReferralForm from './ReferralForm';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Incoming', href: '/incoming' },
    { title: 'Referral form', href: '/referrals/create' },
];

type Formtype = {
    patientFirstName: string;
    patientMiddleName: string;
    patientLastName: string;
    patientSuffix: string;
    patientBirthDate: string;
    patientSex: string;
    patientCivilStatus: string;
    phone: string;
    gender: string;
    address: string;
    bio: string;
    profilePic: string;
    patientStreetAddress: string;
    region: string;
    province: string;
    city: string;
    barangay: string;
};

const ProfileForm = () => {
    const nameInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors } = useForm<Formtype>({
        patientFirstName: '',
        patientMiddleName: '',
        patientLastName: '',
        patientSuffix: '',
        patientBirthDate: '',
        patientSex: '',
        patientCivilStatus: '',
        phone: '',
        gender: '',
        address: '',
        bio: '',
        profilePic: '',
        patientStreetAddress: '',
        region: '',
        province: '',
        city: '',
        barangay: '',
    });

    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);




    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setData(e.target.id, e.target.value);
    };


    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('profile.store'), { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <form onSubmit={handleSubmit} className="p-4 space-y-3 max-w-6xl ml-1 border-0.5 shadow-lg rounded-sm m-4">
                <Head title="Referral Form" />
                <div className="grid grid-cols-2 items-center gap-4 mb-4">
                    <h1 className="flex items-center gap-2 text-md font-semibold">
                        <User className="w-5 h-5" />
                        Referral form
                    </h1>
                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            Submit
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 items-start">
                    <Card className="sm:col-span-1">
                        <CardContent className="flex justify-center">
                            <Avatar className="w-48 h-48">
                                <AvatarImage src="https://github.com/shadcn.png" />
                                <AvatarFallback>CN</AvatarFallback>
                            </Avatar>
                        </CardContent>
                    </Card>

                    <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
                        <h2 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
                            <User className="w-5 h-5" />
                            Patient Information
                        </h2>

                        <div>
                            <Label htmlFor="patientFirstName">Firstname</Label>
                            <Input
                                id="patientFirstName"
                                name="patientFirstName"
                                value={data.patientFirstName}
                                placeholder="Firstname"
                                onChange={handleChange}
                            />
                            {errors.patientFirstName && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientFirstName}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="patientMiddleName">Middle name</Label>
                            <Input
                                id="patientMiddleName"
                                name="patientMiddleName"
                                value={data.patientMiddleName}
                                placeholder="Middle name"
                                onChange={handleChange}
                            />
                            {errors.patientMiddleName && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientMiddleName}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="patientLastName">Last name</Label>
                            <Input
                                id="patientLastName"
                                name="patientLastName"
                                value={data.patientLastName}
                                placeholder="Last name"
                                onChange={handleChange}
                            />
                            {errors.patientLastName && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientLastName}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="patientSuffix">Suffix</Label>
                            <Select
                                value={data.patientSuffix}
                                onValueChange={(value) => setData('patientSuffix', value)}
                                disabled={processing}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select Suffix" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Jr">Jr</SelectItem>
                                    <SelectItem value="Sr">Sr</SelectItem>
                                    <SelectItem value="I">I</SelectItem>
                                    <SelectItem value="II">II</SelectItem>
                                    <SelectItem value="III">III</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.patientSuffix && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientSuffix}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="patientBirthDate">Date of Birth</Label>
                            <Input
                                type="date"
                                id="patientBirthDate"
                                name="patientBirthDate"
                                value={data.patientBirthDate}
                                onChange={handleChange}
                            />
                            {errors.patientBirthDate && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientBirthDate}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="patientSex">Sex</Label>
                            <Select
                                value={data.patientSex}
                                onValueChange={(value) => setData('patientSex', value)}
                                disabled={processing}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select Sex" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Male">Male</SelectItem>
                                    <SelectItem value="Female">Female</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.patientSex && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientSex}</p>
                            )}
                        </div>

                        <div className="md:col-span-2">
                            <Label htmlFor="patientCivilStatus">Civil Status</Label>
                            <Select
                                value={data.patientCivilStatus}
                                onValueChange={(value) => setData('patientCivilStatus', value)}
                                disabled={processing}
                            >
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder="Select Civil Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Single">Single</SelectItem>
                                    <SelectItem value="Married">Married</SelectItem>
                                    <SelectItem value="Widowed">Widowed</SelectItem>
                                    <SelectItem value="Separated">Separated</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.patientCivilStatus && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientCivilStatus}</p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
                    <h2 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
                        <Map className="w-5 h-5" />
                        Demographics
                    </h2>

                    <div className="md:col-span-2">
                        <Label htmlFor="patientStreetAddress" className="text-semibold">Address</Label>
                        <Textarea
                            id="patientStreetAddress"
                            name="patientStreetAddress"
                            value={data.patientStreetAddress}
                            onChange={handleChange}
                            placeholder="Street Address"
                        />
                        {errors.patientStreetAddress && (
                            <p className="text-xs text-red-500 mt-1">{errors.patientStreetAddress}</p>
                        )}
                    </div>

                    <div className="md:col-span-2">
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
                            canCreate={false}
                        />
                    </div>
                </div>

                <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
                    <ReferralForm />
                </div>


            </form>
        
        </AppLayout>
    );
};

export default ProfileForm;
