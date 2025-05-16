import AppLayout from '@/layouts/app-layout';
import React, { Suspense, lazy, useState, useEffect } from "react";
import axios from "axios";
import Swal from "sweetalert2";
import PatientInfo from './PatientProfile';
import ReferralInfo from './ReferralInfo';
import { BreadcrumbItem } from './types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Incoming Referral', href: '/incoming' },
  { title: 'Patient profile', href: '/incoming/profile' },
];

type Profile = {
  fname: string;
  mname: string;
  lname: string;
  dob: string;
  age: string;
  avatar: string;
  guard: string;
  role: string;
  sex?: string;
};

type Demographics = {
  strt: string;
  regname: string;
  provname: string;
  barangay: string;
  zipcode: string;
  region?: string;
  province?: string;
  city?: string;
  streetaddress?: string;
};

type Referral = {
  LogID: string;
  reason: string;
  type: string;
  category: string;
  date: string;
};

type ReferralOrigin = {
  facility_name: string;
  hfhudcode: string;

};

type ReferralDest = {
  facility_name: string;
  hfhudcode: string;

};

type ListProps = {
  refreshKey: any;
  id: number | null;
  is_include: boolean | null;
  onSave: () => void;
};

const IncomingProfile = ({ onSave, refreshKey, id: LogID, is_include }: ListProps) => {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [demographics, setDemographics] = useState<Demographics | null>(null);
  const [referral_origin, setOrigin] = useState<ReferralOrigin | null>(null);
  const [referral_dest, setDestination] = useState<ReferralDest | null>(null);
  const [referral, setReferral] = useState<Referral | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState<'overview' | 'history' | 'notes' | 'activity'>('overview');

  const fetchPatientInfo = async () => {
    if (!LogID) return;

    try {
      setLoading(true);
      const res = await axios.get(`/patient-profile/${LogID}`);
      setProfile(res.data.profile);
      setDemographics(res.data.demographics);

    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Failed to load patient information", "error");
    } finally {
      setLoading(false);
    }
  };


  const fetchReferralInfo = async () => {
    if (!LogID) return;

    try {
      setLoading(true);
      const res = await axios.get(`/referral-information/${LogID}`);
      setReferral(res.data.referral_info); // Ensure your backend returns this
      setOrigin(res.data.origin);
      setDestination(res.data.destination);
    } catch (err) {
      console.error(err);
      Swal.fire("Error", "Failed to load referral information", "error");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPatientInfo();
  }, [LogID]);

  useEffect(() => {
    fetchReferralInfo();
  }, [LogID]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
   <div className="border rounded-xl shadow-xl space-y-6 m-2">

        <div className="flex flex-col lg:flex-row gap-2 p-4 ">

          {/* Left: Patient Info */}
          <div className="w-full lg:w-1/2 space-y-2">
            <PatientInfo profile={profile} demographics={demographics} />
          </div>

          {/* Right: Referral Info */}
          <div className="w-full lg:w-1/2 space-y-2">
            <ReferralInfo referral={referral} referral_origin={referral_origin} referral_dest={referral_dest} />
          </div>
        </div>
      </div>

      {/* Tab Menu */}
      <div className="flex flex-col lg:flex-row gap-4 p-4">


        <nav className="flex flex-col space-y-2 text-sm font-medium text-gray-600">
          {['overview', 'history', 'notes', 'activity'].map(tab => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab as typeof activeTab)}
              className={`text-left px-4 py-2 rounded-sm transition-colors ${activeTab === tab
                  ? 'bg-secondary text-white'
                  : 'hover:bg-gray-100'
                } capitalize`}
            >
              {tab}
            </button>
          ))}
        </nav>

      </div>
    </AppLayout>
  );
};

export default IncomingProfile;
