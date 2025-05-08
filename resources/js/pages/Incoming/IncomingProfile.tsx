import AppLayout from '@/layouts/app-layout';
import React, { useState, useEffect } from "react";
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

type ListProps = {
  refreshKey: any;
  id: number | null;
  is_include: boolean | null;
  onSave: () => void;
};

const IncomingProfile = ({ onSave, refreshKey, id: LogID, is_include }: ListProps) => {
  const [profile, setProfile] = useState<Profile | null>(null);
  const [demographics, setDemographics] = useState<Demographics | null>(null);
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

  useEffect(() => {
    fetchPatientInfo();
  }, [LogID]);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <div className="flex flex-col lg:flex-row gap-4 p-4">
        
        {/* Left: Patient Info */}
        <div className="w-full lg:w-1/2 space-y-4">
          <PatientInfo profile={profile} demographics={demographics} />
          
        </div>
        <div className="w-full lg:w-1/2 space-y-4">
        <ReferralInfo profile={profile} demographics={demographics} />
        </div>

        {/* Right: Tabbed Content */}
      
         
        </div>
        <div className="border-t my-4" />

        <div className="flex flex-col lg:flex-row gap-4 p-4">
        <div className="border-b">
        <h3 className="text-sm font-semibold  uppercase tracking-wide">Menu</h3>
        <nav className="flex flex-col space-y-2 text-sm font-medium text-gray-600">
            {['overview', 'history', 'notes', 'activity'].map(tab => (
                <button
                key={tab}
                onClick={() => setActiveTab(tab as typeof activeTab)}
                className={`text-left px-4 py-2 rounded-sm transition-colors ${
                    activeTab === tab
                    ? 'bg-secondary text-white'
                    : 'hover:bg-gray-100'
                } capitalize`}
                >
                {tab}
                </button>
            ))}
            </nav>

          </div>

            
        </div>
     
    </AppLayout>
  );
};

export default IncomingProfile;
