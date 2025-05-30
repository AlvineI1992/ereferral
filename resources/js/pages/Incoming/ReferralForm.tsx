
import AppLayout from '@/layouts/app-layout';
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import React from 'react';
import { useForm, Head} from '@inertiajs/react';
import { useRef, useEffect } from 'react';
import axios from 'axios';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DemographicSelector from '../Demographics/Demographics_selector';
import { InfoIcon } from 'lucide-react';
import HospitalSelector  from '../Ref_Facilities/HospitalSelector';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const ReferralForm = () => {
    const nameInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors } = useForm({
        LogID: '',
        typeOfReferral: '',
        referralCategory: '',
        referralReason: '',
        otherTypeOfReferral: '',
        otherReferralReason:''
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

    useEffect(() => {

        axios.get('/facilities-list')
            .then(res => setHospital(res.data.data))
            .catch(err => console.error(err));

        nameInputRef.current?.focus();
    }, []);


    return (
        <div className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1 ">
            <h1 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1 ">
                <InfoIcon className="w-5 h-5" />
                Referral Information
            </h1>
            <div className="md:col-span-1">
                <Label htmlFor="LogID" className="text-semibold">Datetime called</Label>
                <Input
                    type="datetime-local"
                    id="LogID"
                    name="LogID"
                    value={data.LogID}
                    placeholder="Transaction code"
                    onChange={handleChange}
                />
            </div>
            <div className="md:col-span-1">
                <Label htmlFor="refferalDate" className="text-semibold">Datetime referred</Label>
                <Input
                    type="datetime-local"
                    id="refferalDate"
                    name="refferalDate"
                    value={data.refferalDate}
                    placeholder="Referral date"
                    onChange={handleChange}
                />
                {errors.referralCategory && (
                    <p className="text-xs text-red-500 mt-1">{errors.referralCategory}</p>
                )}
            </div>

            <div className="md:col-span-1">
                <Label htmlFor="LogID" className="text-semibold">Referring Facility</Label>
                <HospitalSelector/>
            </div>
            <div className="md:col-span-1">
                <Label htmlFor="refferalDate" className="text-semibold">Referral Facility</Label>
                <Input
                    type="datetime-local"
                    id="refferalDate"
                    name="refferalDate"
                    value={data.refferalDate}
                    placeholder="Referral date"
                    onChange={handleChange}
                />
                {errors.referralCategory && (
                    <p className="text-xs text-red-500 mt-1">{errors.referralCategory}</p>
                )}
            </div>

            <div className="md:col-span-2">
                <Label htmlFor="LogID" className="text-semibold">Transaction code</Label>
                <Input
                    id="LogID"
                    name="LogID"
                    value={data.LogID}
                    placeholder="Transaction code"
                    onChange={handleChange}
                />
            </div>
            <div className="md:col-span-1">
                <Label htmlFor="typeOfReferral" className="text-semibold">Type of referral</Label>
                <Select
                    value={data.typeOfReferral}
                    onValueChange={(value) => handleChange({ target: { name: 'typeOfReferral', value } })}
                    disabled={processing}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Type of referral" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="CONSU">CONSULTATION</SelectItem>
                        <SelectItem value="DIAGT">DIAGNOSTIC</SelectItem>
                        <SelectItem value="TRANS">TRANSFER</SelectItem>
                        <SelectItem value="OTHER">OTHERS</SelectItem>
                    </SelectContent>
                </Select>
                {errors.typeOfReferral && (
                    <p className="text-xs text-red-500 mt-1">{errors.typeOfReferral}</p>
                )}
                {data.typeOfReferral === 'OTHER' && (
                    <div className="mt-4">
                        <Label htmlFor="otherTypeOfReferral" className="font-semibold">Please specify</Label>
                        <Input
                            id="otherTypeOfReferral"
                            name="otherTypeOfReferral"
                            value={data.otherTypeOfReferral}
                            onChange={handleChange}
                            placeholder="Specify other referral type"
                        />
                        {errors.otherTypeOfReferral && (
                            <p className="text-xs text-red-500 mt-1">{errors.otherTypeOfReferral}</p>
                        )}
                    </div>
                )}
            </div>

            <div className="md:col-span-1">
                <Label htmlFor="referralCategory" className="text-semibold">Category</Label>
                <Select
                    value={data.referralCategory}
                    onValueChange={(value) => handleChange({ target: { name: 'referralCategory', value } })}
                    disabled={processing}
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Referral category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ER">Emergency</SelectItem>
                        <SelectItem value="OPD">Outpatient</SelectItem>
                    </SelectContent>
                </Select>
                {errors.referralCategory && (
                    <p className="text-xs text-red-500 mt-1">{errors.referralCategory}</p>
                )}
            </div>
            <div className="md:col-span-1">
                <label className="mb-1 block text-sm font-medium ">
                    Reason for Referral
                </label>

                <Select
                    value={data.referralReason}
                    onValueChange={(value) => handleChange({ target: { name: 'referralReason', value } })}

                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Select" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="NOROM">No room available</SelectItem>
                        <SelectItem value="SEASO">Seek advise/second opinion</SelectItem>
                        <SelectItem value="SESPE">Seek specialized evaluation</SelectItem>
                        <SelectItem value="SEFTA">Seek further treatment appropriate to the case</SelectItem>
                        <SelectItem value="NOEQP">No equipment available</SelectItem>
                        <SelectItem value="NOPRO">No procedure available</SelectItem>
                        <SelectItem value="NOLAB">No laboratory available</SelectItem>
                        <SelectItem value="NODOC">No available doctor</SelectItem>
                        <SelectItem value="OTHER">Other</SelectItem>
                    </SelectContent>
                </Select>
                {errors.referralReason && (<p className="text-xs text-red-500 mt-1">{errors.referralReason}</p>)}

                {data.referralReason === 'OTHER' && (
                    <div className="mt-4">
                        <Label htmlFor="otherTypeOfReferral" className="font-semibold">Please specify</Label>
                        <Input
                            id="otherReferralReason"
                            name="otherReferralReason"
                            value={data.otherReferralReason}
                            onChange={handleChange}
                            placeholder="Specify other referral type"
                        />
                        {errors.otherReferralReason && (
                            <p className="text-xs text-red-500 mt-1">{errors.otherReferralReason}</p>
                        )}
                    </div>
                )}
            </div>
        </div>



    );
};

export default ReferralForm;
