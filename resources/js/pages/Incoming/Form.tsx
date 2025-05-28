import React from 'react';
import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
            <form onSubmit={handleSubmit} className="mx-auto p-2 space-y-6 ">
                <h2 className="text-lg font-semibold text-left">Referral Form</h2>
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:gap-6 items-start">
                    
                    {/* Avatar Card */}
                    <Card className="sm:col-span-1 mx-1">

                        <CardContent className="flex justify-center">
                            <Avatar className="w-53 h-53">
                                <AvatarImage src="https://github.com/shadcn.png" />
                                <AvatarFallback>CN</AvatarFallback>
                            </Avatar>
                        </CardContent>

                    </Card>

                    {/* Input Fields Section */}
                    <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-1">
                    <legend className="font-bold text-lg mb-1">Patient Information</legend>
                    <div className="font-bold text-lg mb-1"></div>
                        {/* Firstname */}
                        <div>
                            <Label htmlFor="patientFirstName" className="font-bold">Firstname</Label>
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
                            <Label htmlFor="patientMiddleName" className="font-bold">Middle name</Label>
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
                        <div className="md:col-span-1">
                            <Label htmlFor="patientLastName" className="font-bold">Last name</Label>
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

                        {/* Suffix Select */}
                        <div className="md:col-span-1">
                            <Label htmlFor="patientSuffix" className="font-bold">Suffix:</Label> {/* Changed htmlFor to patientSuffix */}
                            <Select
                                value={data.patientSuffix} // Assuming you'd have a patientSuffix in your data state
                                onValueChange={(value) => handleChange({ target: { name: 'patientSuffix', value } })} // Simulating handleChange for Select
                                disabled={processing}
                                aria-labelledby="patientSuffix"
                            >
                                <SelectTrigger className="w-full"> {/* Removed redundant classes */}
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
                            {/* Ensure correct error key for suffix */}
                            {errors.patientSuffix && (
                                <p className="text-xs text-red-500 mt-1">{errors.patientSuffix}</p>
                            )}
                        </div>
                        
                        <div>
                            <Label htmlFor="dob" className="font-bold">Date of Birth</Label>
                            <Input id="dob" type="date" name="dob" value={data.dob} onChange={handleChange} />
                            {errors.dob && <p className="text-sm text-red-500">{errors.dob}</p>}
                        </div>
                        <div>
                            <Label htmlFor="dob" className="font-bold">Sex</Label>
                            <Select
                                value={data.patientSuffix} // Assuming you'd have a patientSuffix in your data state
                                onValueChange={(value) => handleChange({ target: { name: 'patientSuffix', value } })} // Simulating handleChange for Select
                                disabled={processing}
                                aria-labelledby="patientSuffix"
                            >
                                <SelectTrigger className="w-full"> {/* Removed redundant classes */}
                                    <SelectValue placeholder="Select Sex" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Jr">Male</SelectItem>
                                    <SelectItem value="Sr">Female</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.dob && <p className="text-sm text-red-500">{errors.dob}</p>}
                        </div>
                        <div className="md:col-span-2">
                            <Label htmlFor="dob" className="font-bold">Civil status</Label>
                            <Select
                                value={data.patientSuffix} // Assuming you'd have a patientSuffix in your data state
                                onValueChange={(value) => handleChange({ target: { name: 'patientSuffix', value } })} // Simulating handleChange for Select
                                disabled={processing}
                                aria-labelledby="patientSuffix"
                            >
                                <SelectTrigger className="w-full"> {/* Removed redundant classes */}
                                    <SelectValue placeholder="Civil status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Jr">Male</SelectItem>
                                    <SelectItem value="Sr">Female</SelectItem>
                                </SelectContent>
                            </Select>
                            {errors.dob && <p className="text-sm text-red-500">{errors.dob}</p>}
                        </div>
                    </div>
                </div>







                <div className="grid grid-cols-1 md:grid-cols-3 gap-1">


                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email"
                            type="email"
                            name="email"
                            value={data.email} onChange={handleChange}

                        />
                        {errors.email && <p className="text-sm text-red-500">{errors.email}</p>}
                    </div>







                    <div className="md:col-span-2">
                        <Label htmlFor="address">Address</Label>
                        <Input id="address" name="address" value={data.address} onChange={handleChange} />
                        {errors.address && <p className="text-sm text-red-500">{errors.address}</p>}
                    </div>
                </div>

                <div>
                    <Label htmlFor="bio">Bio</Label>
                    <Textarea id="bio" name="bio" value={data.bio} onChange={handleChange} rows={4} />
                    {errors.bio && <p className="text-sm text-red-500">{errors.bio}</p>}
                </div>

                <div>
                    <Label htmlFor="profilePic">Profile Picture</Label>
                    <Input id="profilePic" name="profilePic" type="file" onChange={handleChange} />
                    {errors.profilePic && <p className="text-sm text-red-500">{errors.profilePic}</p>}
                </div>

                <div className="text-center">
                    <Button type="submit" disabled={processing}>
                        {processing ? 'Submitting...' : 'Submit Profile'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
};

export default ProfileForm;
