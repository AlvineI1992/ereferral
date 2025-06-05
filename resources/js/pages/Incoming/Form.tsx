import { useRef, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import React from 'react';

import AppLayout from '@/layouts/app-layout';
import { User, Save, Map } from "lucide-react";
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select';
import {
  Card,
  CardContent,
} from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

import DemographicSelector from '../Demographics/Demographics_selector';
import ReferralForm from './ReferralForm';
import { cn } from '@/lib/utils';

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
      <form onSubmit={handleSubmit} className="p-4 space-y-3 max-w-6xl ml-1 border-0.5 shadow-lg rounded-sm m-4">
        <Head title="Referral Form" />
        <div className="grid grid-cols-2 items-center gap-4 mb-4">
          <h1 className="flex items-center gap-2 text-lg font-semibold">
            <User className="w-5 h-5" />
            Referral form
          </h1>
          <div className="flex justify-end">
            <Button type="submit" disabled={processing}>
              <Save className="w-5 h-5" /> Submit
            </Button>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-1 sm:grid-cols-4 items-start">
          <Card className="sm:col-span-1">
            <CardContent className="flex justify-center">
              <Avatar className="w-40 h-40">
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
                  className={`peer block w-full rounded-md border px-3 pt-6 pb-2 text-sm placeholder-transparent focus:outline-none focus:ring-1 ${
                    errors[id as keyof typeof errors] ? 'border-red-500 ring-red-500' : 'border-gray-300 ring-gray-300 focus:ring-primary'
                  }`}
                />
                <label htmlFor={id} className={floatingLabelClass}>
                  {label}
                </label>
                {errors[id as keyof typeof errors] && (
                  <p className="text-[10px] text-red-500 mt-1">{errors[id as keyof typeof errors]}</p>
                )}
              </div>
            ))}

            {[
              {
                id: 'patientSuffix',
                label: 'Suffix',
                options: ['Jr', 'Sr', 'I', 'II', 'III'],
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
                  <p className="text-[10px] text-red-500 mt-1">{errors[id as keyof typeof errors]}</p>
                )}
              </div>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-1">
          <h2 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1">
            <Map className="w-5 h-5" />
            Demographics
          </h2>

          <div className="md:col-span-2 relative mt-1">
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
            {errors.patientStreetAddress && (
              <p className="text-[10px] text-red-500 mt-1">{errors.patientStreetAddress}</p>
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
