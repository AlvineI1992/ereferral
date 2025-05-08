import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info, UserCircle2Icon, Venus, Mars } from 'lucide-react';

type ProfileData = {
  fname: string;
  mname: string;
  lname?: string;
  dob?: string;
  age?: string;
  sex?: string;
  avatar?: string;
};

type ProfileDemographics = {
  strt: string;
  region: string;
  province?: string;
  city?: string;
  barangay?: string;
  streetaddress?: string;
  zipcode?: string;
};

type Props = {
  profile: ProfileData | null;
  demographics: ProfileDemographics | null;
};

export default function ReferralInfo({ profile, demographics }: Props) {
  if (!profile && !demographics) {
    return <p className="text-sm text-muted-foreground">Loading profile...</p>;
  }

  const fullName = [profile?.fname, profile?.mname, profile?.lname].filter(Boolean).join(' ');
  const initial = profile?.fname?.charAt(0).toUpperCase() || '?';

  return (
    <div className="space-y-6">
      <div className="p-3">
        {/* Header */}
        <div className="flex items-center mb-4">
          <UserCircle2Icon size={24} className="text-primary mr-2" />
          <h2 className="text-xl">Referral information</h2>
        </div>

        {/* Content */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-5">
        
          {/* Profile Info */}
          <div className="space-y-2 text-sm ">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-sm ">
            <p><span className="font-medium ">Reference:</span> {demographics?.streetaddress || 'N/A'}</p>
            <p><span className="font-medium ">Referral Date:</span> {demographics?.barangay || 'N/A'}</p>
            <p><span className="font-medium ">Reason:</span> {demographics?.city || 'N/A'}</p>
            <p><span className="font-medium ">Category:</span> {demographics?.province || 'N/A'}</p>
            <p><span className="font-medium ">Type:</span> {demographics?.region || 'N/A'}</p>
            <p><span className="font-medium ">Zipcode:</span> {demographics?.zipcode || 'N/A'}</p>
          </div>
          </div>
        </div>

        {/* Divider */}
        <div className="border-t my-4" />

        {/* Demographics Info */}
        <div>
          <div className="flex items-center mb-2">
            <Info size={18} className="text-primary mr-2" />
            <h3 className="text-md">Destination</h3>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-0  text-sm">
            <p><span className="font-medium ">Street:</span> {demographics?.streetaddress || 'N/A'}</p>
            <p><span className="font-medium ">Barangay:</span> {demographics?.barangay || 'N/A'}</p>
            <p><span className="font-medium ">City:</span> {demographics?.city || 'N/A'}</p>
            <p><span className="font-medium ">Province:</span> {demographics?.province || 'N/A'}</p>
            <p><span className="font-medium ">Region:</span> {demographics?.region || 'N/A'}</p>
            <p><span className="font-medium ">Zipcode:</span> {demographics?.zipcode || 'N/A'}</p>
          </div>
        </div>
      </div>
    </div>
  );
}
