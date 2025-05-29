import React from 'react';
import { useForm,Head } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DemographicSelector from '../Demographics/Demographics_selector';
import ReferralForm from './ReferralForm';
import { Map, User} from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"

import AppLayout from '@/layouts/app-layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Incoming',
        href: '/incoming',
    },
    {
        title: 'Referral form',
        href: '/referrals/create',
    },
];
const ProfileForm = () => {
    const { data, setData, post, processing, errors } = useForm({
        patientFirstName: '',
        patientLastName: '',
        patientMiddleName: '',
        patientSuffix: '',
        phone: '',
        gender: '',
        dob: '',
        address: '',
        bio: '',
        profilePic: null,
    });

    const handleChange = (e) => {
        const { name, type, value, files } = e.target;
        if (type === 'file') {
            setData(name, files[0]);
        } else {
            setData(name, value);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('profile.store'), {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
<form onSubmit={handleSubmit} className="p-4 space-y-8 max-w-6xl ml-1">
    <Head title="Referral Form" />
    <h1 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
            <User className="w-5 h-5" />
        Referral form
    </h1>
    <div className="grid grid-cols-1 gap-2 sm:grid-cols-4 items-start">
        {/* Avatar Card */}
        <Card className="sm:col-span-1">
            <CardContent className="flex justify-center">
                <Avatar className="w-48 h-48">
                    <AvatarImage src="https://github.com/shadcn.png" />
                    <AvatarFallback>CN</AvatarFallback>
                </Avatar>
            </CardContent>
        </Card>

        {/* Input Fields Section */}
        <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
        <h1 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
            <User className="w-5 h-5" />
            Patient Information
        </h1>

            {/* Firstname */}
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

            {/* Middle name */}
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

            {/* Last name */}
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

            {/* Suffix */}
            <div>
                <Label htmlFor="patientSuffix">Suffix</Label>
                <Select
                    value={data.patientSuffix}
                    onValueChange={(value) => handleChange({ target: { name: 'patientSuffix', value } })}
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

            {/* Date of Birth */}
            <div>
                <Label htmlFor="dob">Date of Birth</Label>
                <input
                    type="date"
                    id="dob"
                    name="dob"
                    value={data.dob}
                    onChange={handleChange}
                    className="w-full rounded-md border border-gray-300 p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                />

                {errors.dob && (
                    <p className="text-xs text-red-500 mt-1">{errors.dob}</p>
                )}
            </div>

            {/* Sex */}
            <div>
                <Label htmlFor="sex">Sex</Label>
                <Select
                    value={data.sex}
                    onValueChange={(value) => handleChange({ target: { name: 'sex', value } })}
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
                {errors.sex && (
                    <p className="text-xs text-red-500 mt-1">{errors.sex}</p>
                )}
            </div>

            {/* Civil Status */}
            <div className="md:col-span-2">
                <Label htmlFor="civilStatus">Civil Status</Label>
                <Select
                    value={data.civilStatus}
                    onValueChange={(value) => handleChange({ target: { name: 'civilStatus', value } })}
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
                {errors.civilStatus && (
                    <p className="text-xs text-red-500 mt-1">{errors.civilStatus}</p>
                )}
            </div>
        </div>
    </div>
    <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
        <h1 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
            <Map className="w-5 h-5" />
            Demographics
        </h1>
        <div className="md:col-span-2">
                    <Label htmlFor="patientStreetAddress" className="text-semibold">Address</Label>
                    <Textarea
                        id="patientStreetAddress"
                        name="patientStreetAddress"
                        value={data.patientStreetAddress}
                        onChange={handleChange}
                        placeholder="Street Address"
                        className="mt-1"
                    />
                    {errors.patientStreetAddress && (
                        <p className="text-xs text-red-500 mt-1">{errors.patientStreetAddress}</p>
                    )}
                </div>
                <div className="md:col-span-2">
                      <DemographicSelector canCreate={false} variant="horizontal"/>
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
