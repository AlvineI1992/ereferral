import { Head, useForm } from '@inertiajs/react';
import React, { useEffect,useRef } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { CameraIcon, Map, Save, User } from 'lucide-react';

import DemographicSelector from '../Demographics/Demographics_selector';
import ReferralForm from './ReferralForm';

const breadcrumbs = [
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
        post(route('referral.store'), { forceFormData: true });
    };

    const floatingLabelClass = `
    pointer-events-none absolute left-3 top-1 text-[10px] text-gray-500
    transition-all duration-200 ease-in-out peer-placeholder-shown:top-2
    peer-placeholder-shown:text-sm peer-placeholder-shown:text-gray-400
    peer-focus:top-0 peer-focus:text-[10px] peer-focus:text-gray-700
  `;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <form onSubmit={handleSubmit} className="border-0.5 m-4 ml-1 max-w-6xl space-y-3 rounded-sm p-4 shadow-lg">
                <Head title="Referral Form" />
                <div className="mb-4 grid grid-cols-2 items-center gap-4">
                    <h1 className="flex items-center gap-2 text-lg font-semibold">
                        <User className="h-5 w-5" />
                        Referral form
                    </h1>

                    <div className="flex justify-end">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Save className="h-5 w-5" /> Submit
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 items-start gap-1 sm:grid-cols-4">
                    <Card className="flex flex-col justify-between p-4 sm:col-span-1">
                        <CardContent className="flex justify-center">
                            <Avatar className="h-24 w-24">
                                <AvatarImage src="https://github.com/shadcn.png" alt="Avatar" />
                                <AvatarFallback>CN</AvatarFallback>
                            </Avatar>
                        </CardContent>
                        <CardFooter className="flex-col gap-1">
                            <Button type="submit" variant="outline" className="w-full">
                                <CameraIcon className="h-5 w-5" />
                                Take Photo
                            </Button>
                        </CardFooter>
                    </Card>
                    <div className="grid grid-cols-1 gap-1 sm:col-span-3 md:grid-cols-2">
                        <h2 className="text-md mb-1 flex items-center gap-2 font-semibold md:col-span-2">
                            <User className="h-5 w-5" />
                            Patient Information
                        </h2>

                        {[
                            { id: 'patientFirstName', label: 'Firstname' },
                            { id: 'patientMiddleName', label: 'Middlename' },
                            { id: 'patientLastName', label: 'Lastname' },

                            { id: 'patientBirthDate', label: 'Birthdate', type: 'date' },
                        ].map(({ id, label, type = 'text' }) => (
                            <div key={id} className="relative mt-1">
                                <Input
                                    id={id}
                                    type={type}
                                    value={data[id as keyof Formtype]}
                                    onChange={handleChange}
                                    placeholder=" "
                                    className={`peer block w-full rounded-md border px-3 pt-6 pb-2 text-sm placeholder-transparent focus:ring-1 focus:outline-none ${
                                        errors[id as keyof typeof errors]
                                            ? 'border-red-500 ring-red-500'
                                            : 'focus:ring-primary border-gray-300 ring-gray-300'
                                    }`}
                                />
                                <label htmlFor={id} className={floatingLabelClass}>
                                    {label}
                                </label>
                                {errors[id as keyof typeof errors] && (
                                    <p className="mt-1 text-[10px] text-red-500">{errors[id as keyof typeof errors]}</p>
                                )}
                            </div>
                        ))}

                        {[
                            {
                                id: 'patientSuffix',
                                label: 'Suffix',
                                options: ['N/A', 'Jr', 'Sr', 'I', 'II', 'III'],
                            },
                            {
                                id: 'patientSex',
                                label: 'Sex',
                                options: ['Male', 'Female'],
                            },
                            {
                                id: 'patientCivilStatus',
                                label: 'Civil Status',
                                options: ['Single', 'Married', 'Widowed', 'Separated'],
                            },
                        ].map(({ id, label, options }) => (
                            <div key={id} className="relative mt-1">
                                <label htmlFor={id} className={floatingLabelClass}>
                                    {label}
                                </label>
                                <Select
                                    value={data[id as keyof Formtype]}
                                    onValueChange={(value) => setData(id as keyof Formtype, value)}
                                    disabled={processing}
                                >
                                    <SelectTrigger
                                        id={id}
                                        className={`peer w-full pt-5 pb-2 pl-3 ${
                                            errors[id as keyof typeof errors] ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''
                                        }`}
                                    >
                                        <SelectValue placeholder=" " />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.map((opt) => (
                                            <SelectItem key={opt} value={opt}>
                                                {opt}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors[id as keyof typeof errors] && (
                                    <p className="mt-1 text-[10px] text-red-500">{errors[id as keyof typeof errors]}</p>
                                )}
                            </div>
                        ))}

                        <Input
                            id="contact"
                            ref={nameInputRef}
                            placeholder="Contact number"
                            onChange={handleChange}
                            className="mt-1 block w-full"
                            autoComplete="off"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-1 md:grid-cols-2">
                    <h2 className="text-md mb-1 flex items-center gap-2 font-semibold md:col-span-2">
                        <Map className="h-5 w-5" />
                        Demographics
                    </h2>

                    <div className="relative mt-1 md:col-span-2">
                        <Textarea
                            id="patientStreetAddress"
                            value={data.patientStreetAddress}
                            onChange={handleChange}
                            placeholder=" "
                            className="peer w-full pt-6 pb-2"
                        />
                        <label htmlFor="patientStreetAddress" className={floatingLabelClass}>
                            Street Address
                        </label>
                        {errors.patientStreetAddress && <p className="mt-1 text-[10px] text-red-500">{errors.patientStreetAddress}</p>}
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
                            canCreate={true}
                            errors={{
                                region: errors.region,
                                province: errors.province,
                                city: errors.city,
                                barangay: errors.barangay,
                            }}
                        />
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-1 sm:col-span-3 md:grid-cols-2">
                    <ReferralForm    
                      LogID=""
                  typeOfReferral=""
                  referralCategory=""
                  referralReason=""
                  otherTypeOfReferral=""
                  otherReferralReason=""
                  refferalDate=""  errors={errors} />
                </div>
            </form>
        </AppLayout>
    );
};

export default ProfileForm;
