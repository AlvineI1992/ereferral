import React, { useRef, useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import axios from 'axios';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import HospitalSelector from '../Ref_Facilities/HospitalSelector';
import { InfoIcon } from 'lucide-react';

type ReferralFormProps = {
  LogID?: string;
  typeOfReferral?: string;
  referralCategory?: string;
  referralReason?: string;
  otherTypeOfReferral?: string;
  otherReferralReason?: string;
  refferalDate?: string;
  errors?: Record<string, string>;
};

const ReferralForm = ({
  LogID = '',
  typeOfReferral = '',
  referralCategory = '',
  referralReason = '',
  otherTypeOfReferral = '',
  otherReferralReason = '',
  refferalDate = '',
  errors: pageErrors = {},
}: ReferralFormProps) => {
  const nameInputRef = useRef<HTMLInputElement>(null);
  const [referringFacilityCode, setReferringFacilityCode] = useState('');
  const [referralFacilityCode, setReferralFacilityCode] = useState('');
  const [responseCode, setResponseCode] = useState(null);
  const [error, setError] = useState(null);
  const [hospitals, setHospitals] = useState([]);
  const [referringPopoverOpen, setReferringPopoverOpen] = useState(false);
  const [referralPopoverOpen, setReferralPopoverOpen] = useState(false);

  const { data, setData, post, processing, errors } = useForm({
    LogID,
    typeOfReferral,
    referralCategory,
    referralReason,
    otherTypeOfReferral,
    otherReferralReason,
    refferalDate,
    referringFacility: '',
    referralFacility: '',
  });

  useEffect(() => {
    axios.get('/facilities-list')
      .then(res => setHospitals(res.data.data))
      .catch(err => console.error(err));

    nameInputRef.current?.focus();
  }, []);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('profile.store'), {
      forceFormData: true,
    });
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value, type, files } = e.target as HTMLInputElement;
    if (type === 'file' && files) {
      setData(name, files[0]);
    } else {
      setData(name, value);
    }
  };

  const getError = (field: string) => errors[field] || pageErrors[field];

  return (
    <form onSubmit={handleSubmit} className="sm:col-span-3 grid grid-cols-1 md:grid-cols-2 gap-1">
      <h1 className="flex items-center gap-2 text-md font-semibold md:col-span-2 mb-1 ">
        <InfoIcon className="w-5 h-5" />
        Referral Information
      </h1>

      <div className="md:col-span-1">
        <Label htmlFor="calledDate" className="text-semibold">Datetime called</Label>
        <Input
          type="datetime-local"
          id="calledDate"
          name="calledDate"
          value={data.LogID}
          onChange={handleChange}
          ref={nameInputRef}
        />
        {getError('calledDate') && <p className="text-[10px] text-red-500 mt-1">{getError('calledDate')}</p>}
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
        {getError('refferalDate') && <p className="text-[10px] text-red-500 mt-1">{getError('refferalDate')}</p>}
      </div>

      <div className="md:col-span-1">
        <Label className="text-semibold">Referring Facility</Label>
        <HospitalSelector
          hospitals={hospitals}
          selectedHospital={referringFacilityCode}
          setSelectedHospital={(code) => {
            setReferringFacilityCode(code);
            setData('referringFacility', code);
          }}
          setData={setData}
          hospitalPopoverOpen={referringPopoverOpen}
          setHospitalPopoverOpen={setReferringPopoverOpen}
          errors={errors}
        />
      </div>

      <div className="md:col-span-1">
        <Label className="text-semibold">Referral Facility</Label>
        <HospitalSelector
          hospitals={hospitals}
          selectedHospital={referralFacilityCode}
          setSelectedHospital={(code) => {
            setReferralFacilityCode(code);
            setData('referralFacility', code);
          }}
          setData={setData}
          hospitalPopoverOpen={referralPopoverOpen}
          setHospitalPopoverOpen={setReferralPopoverOpen}
          errors={errors}
        />
      </div>

      <div className="md:col-span-2">
        <Label htmlFor="transactionCode" className="text-semibold">Transaction code</Label>
        <Input
          id="transactionCode"
          name="transactionCode"
          value={data.LogID}
          placeholder="Transaction code"
          onChange={handleChange}
        />
        {getError('transactionCode') && <p className="text-[10px] text-red-500 mt-1">{getError('transactionCode')}</p>}
      </div>

      <div className="md:col-span-1">
        <Label htmlFor="typeOfReferral" className="text-semibold">Type of referral</Label>
        <Select
          value={data.typeOfReferral}
          onValueChange={(value) => setData('typeOfReferral', value)}
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
        {getError('typeOfReferral') && <p className="text-[10px] text-red-500 mt-1">{getError('typeOfReferral')}</p>}
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
            {getError('otherTypeOfReferral') && <p className="text-xs text-red-500 mt-1">{getError('otherTypeOfReferral')}</p>}
          </div>
        )}
      </div>

      <div className="md:col-span-1">
        <Label htmlFor="referralCategory" className="text-semibold">Category</Label>
        <Select
          value={data.referralCategory}
          onValueChange={(value) => setData('referralCategory', value)}
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
        {getError('referralCategory') && <p className="text-xs text-red-500 mt-1">{getError('referralCategory')}</p>}
      </div>

      <div className="md:col-span-1">
        <Label className="mb-1 block text-sm font-medium">Reason for Referral</Label>
        <Select
          value={data.referralReason}
          onValueChange={(value) => setData('referralReason', value)}
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
        {getError('referralReason') && <p className="text-xs text-red-500 mt-1">{getError('referralReason')}</p>}
        {data.referralReason === 'OTHER' && (
          <div className="mt-4">
            <Label htmlFor="otherReferralReason" className="font-semibold">Please specify</Label>
            <Input
              id="otherReferralReason"
              name="otherReferralReason"
              value={data.otherReferralReason}
              onChange={handleChange}
              placeholder="Specify other referral reason"
            />
            {getError('otherReferralReason') && <p className="text-xs text-red-500 mt-1">{getError('otherReferralReason')}</p>}
          </div>
        )}
      </div>
    </form>
  );
};

export default ReferralForm;
