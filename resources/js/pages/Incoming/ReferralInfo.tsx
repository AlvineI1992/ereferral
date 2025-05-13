import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info, UserCircle2Icon } from 'lucide-react';

type Referral = {
  logid: string;
  date_ref: string;
  reason: string;
  type: string;
  category: string;
};

type ReferralDemographics = {
  strt: string;
  region: string;
  province?: string;
  city?: string;
  barangay?: string;
  streetaddress?: string;
  zipcode?: string;
};

type Props = {
  referral: Referral | null;
  demographics: ReferralDemographics | null;
};

export default function ReferralInfo({ referral }: Props) {
  if (!referral) {
    return <p className="text-sm text-muted-foreground">Loading referral...</p>;
  }

  return (
    <div className="space-y-6">
      <div className="p-3">
        {/* Header */}
        <div className="flex items-center mb-2">
          <UserCircle2Icon size={24} className="text-primary mr-2" />
          <h2 className="text-xl">Referral Information</h2>
        </div>

        {/* Referral Info */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-5">
          <div className="space-y-2 text-sm">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm">
              <p><span className="font-medium">Reference:</span> {referral.logid || 'N/A'}</p>
              <p><span className="font-medium">Referral Date:</span> {referral.date_ref || 'N/A'}</p>
              <p><span className="font-medium">Type:</span> {referral.type || 'N/A'}</p>
              <p><span className="font-medium">Reason:</span> {referral.reason || 'N/A'}</p>
              <p><span className="font-medium">Category:</span> {referral.category || 'N/A'}</p>
            </div>
          </div>
        </div>

        {/* Divider */}
        <div className="border-t my-4" />

        {/* Demographics Info */}
        <div>
          <div className="flex items-center mb-2">
            <Info size={18} className="text-primary mr-2" />
            <h3 className="text-md">Origin</h3>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-0 text-sm">
            <p><span className="font-medium">Street:</span> {demographics?.streetaddress || 'N/A'}</p>
            <p><span className="font-medium">Barangay:</span> {demographics?.barangay || 'N/A'}</p>
            <p><span className="font-medium">City:</span> {demographics?.city || 'N/A'}</p>
            <p><span className="font-medium">Province:</span> {demographics?.province || 'N/A'}</p>
            <p><span className="font-medium">Region:</span> {demographics?.region || 'N/A'}</p>
            <p><span className="font-medium">Zipcode:</span> {demographics?.zipcode || 'N/A'}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
