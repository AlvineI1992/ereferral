import React, { useEffect, useState } from 'react';
import axios from 'axios';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Heart, Thermometer, Droplet,Ruler,Scale} from 'lucide-react';

type Vitals = {
  BP: string;
  Temp: string;
  HR: string;
  RR: string;
  O2Sats: string;
  Weight: string;
  Height: string;
};

type ReferralClinicalData = {
  LogID: string;
  diagnosis: string;
  history: string;
  physical_examination: string;
  chief_complaint: string;
  findings: string;
  vitals: Vitals;
};

type ClinicalInfoProps = {
  logID: string;
};

export default function ClinicalInfo({ logID }: ClinicalInfoProps) {
  const [clinicalData, setClinicalData] = useState<ReferralClinicalData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!logID) return;

    const fetchClinicalData = async () => {
      try {
        setLoading(true);
        const res = await axios.get(`/referral-clinical/${logID}`);
        setClinicalData(res.data);
      } catch (error) {
        console.error('Failed to fetch clinical data:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchClinicalData();
  }, [logID]);

  if (loading) return <div>Loading clinical data...</div>;
  if (!clinicalData) return <div>No clinical data found.</div>;

  const {
    diagnosis,
    history,
    physical_examination,
    chief_complaint,
    findings,
    vitals,
  } = clinicalData;

  return (
    <div className="space-y-4 p-4">
      <div className="space-y-2 text-sm">
        <p><strong>Chief Complaint:</strong> {chief_complaint || '-'}</p>
        <p><strong>Diagnosis:</strong> {diagnosis || '-'}</p>
        <p><strong>History:</strong> {history || '-'}</p>
        <p><strong>Physical Examination:</strong> {physical_examination || '-'}</p>
        <p><strong>Findings:</strong> {findings || '-'}</p>
      </div>

      <div className="space-y-2">
        <h3 className="font-semibold mt-4">Vital signs</h3>
        <div className="grid grid-cols-2 gap-2 text-sm">
          <p><Heart className="inline w-4 h-4" /> BP: {vitals.BP || '-'}</p>
          <p><Thermometer className="inline w-4 h-4" /> Temp: {vitals.Temp || '-'}</p>
          <p><Droplet className="inline w-4 h-4" /> HR: {vitals.HR || '-'}</p>
          <p>RR: {vitals.RR || '-'}</p>
          <p>O₂ Sats: {vitals.O2Sats || '-'}</p>
          <p><Scale className="inline w-4 h-4" />Weight: {vitals.Weight || '-'}</p>
          <p><Ruler className="inline w-4 h-4" />Height: {vitals.Height || '-'}</p>
        </div>
      </div>
    </div>
  );
}
