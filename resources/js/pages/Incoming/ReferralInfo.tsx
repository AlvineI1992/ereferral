import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info, Ambulance,HospitalIcon  } from 'lucide-react';

type Referral = {
  LogID: string;
  date: string;
  reason: string;
  type: string;
  category: string;
};

type ReferralOrigin = {
  facility_name:string;
  hfhudcode:string;
 
};


type ReferralDest = {
  facility_name:string;
  hfhudcode:string;
 
};

type Props = {
  referral: Referral | null;
  referral_origin: ReferralOrigin | null;
  referral_dest: ReferralDest | null;
};
export default function ReferralInfo({ referral, referral_origin, referral_dest }: Props) {
  if (!referral) {
    return <p className="text-sm text-muted-foreground">Loading referral...</p>;
  }

  return (
    <div className="space-y-6">
      {/* Border box wrapper */}
   
        {/* Header */}
        <div className="flex items-center mb-4">
          <Ambulance size={24} className="text-primary mr-2" />
          <div className="text-lg font-semibold">Referral Information</div>
        </div>

        {/* Referral Info */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-5 mb-4">
          <div className="space-y-2 text-sm">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm">
              <p className="text-lg">{referral.LogID || 'N/A'}</p>
              <p><span className="font-bold">Referral Date:</span> {referral.date || 'N/A'}</p>
              <p><span className="font-bold">Type:</span> {referral.type || 'N/A'}</p>
              <p><span className="font-bold">Reason:</span> {referral.reason || 'N/A'}</p>
              <p><span className="font-bold">Category:</span> {referral.category || 'N/A'}</p>
            </div>
          </div>
        </div>

        {/* Origin Info */}
        <div className="mb-4">
          <div className="flex items-center mb-1">
            <HospitalIcon size={18} className="text-primary mr-1" />
            <h3 className="text-sm font-medium">Origin</h3>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
            <p><span className="font-bold">Facility code:</span> {referral_origin?.hfhudcode || 'N/A'}</p>
            <p><span className="font-bold">Facility name:</span> {referral_origin?.facility_name || 'N/A'}</p>
          </div>
        </div>

        {/* Destination Info */}
        <div>
          <div className="flex items-center mb-1">
            <HospitalIcon size={18} className="text-primary mr-1" />
            <h3 className="text-sm font-medium">Destination</h3>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
            <p><span className="font-bold">Facility code:</span> {referral_dest?.hfhudcode || 'N/A'}</p>
            <p><span className="font-bold">Facility name:</span> {referral_dest?.facility_name || 'N/A'}</p>
          </div>
        </div>
 
    </div>
  );
}
