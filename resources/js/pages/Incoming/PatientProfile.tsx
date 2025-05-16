import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info, UserCircle2Icon, Venus, Mars,MapIcon } from 'lucide-react';

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
  regname: string;
  provname: string;
  barangay: string;
  zipcode: string;
  region?: string;
  province?: string;
  city?: string;
  streetaddress?: string;
};

type Props = {
  profile: ProfileData | null;
  demographics: ProfileDemographics | null;
};

export default function PatientInfo({ profile, demographics }: Props) {
  const fullName = [profile?.fname, profile?.mname, profile?.lname].filter(Boolean).join(' ');
  const initial = profile?.fname?.charAt(0).toUpperCase() || '?';

  return (
    <div className="space-y-6">
      {/* Border box wrapper */}
    
        {/* Header */}
        <div className="flex items-center mb-4">
          <UserCircle2Icon size={24} className="text-primary mr-2" />
          <div className="text-lg font-semibold">Patient Profile</div>
        </div>

        {/* Content */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-5 mb-4">
          {/* Avatar */}
          <Avatar className="w-19 h-19 ring-1 ring-primary shadow-sm">
            <AvatarImage
              src={profile?.avatar || '/default-avatar.jpg'}
              alt={`${fullName}'s avatar`}
            />
            <AvatarFallback>{initial}</AvatarFallback>
          </Avatar>

          {/* Profile Info */}
          <div className="space-y-1 text-sm">
            <p className="text-lg font-medium">{fullName || 'No Name'}</p>
            <p>Age: {profile?.age || 'Unknown'}</p>
            <p>Birthdate: {profile?.dob || 'Unknown'}</p>
            {profile?.sex ? (
              <div className="flex items-center gap-1">
                <span>Sex:</span>
                {profile.sex === 'Male' ? (
                  <Mars className="text-blue-700" size={16} />
                ) : (
                  <Venus className="text-pink-700" size={16} />
                )}
                <span
                  className={`${
                    profile.sex === 'Male' ? 'text-blue-700' : 'text-pink-700'
                  } font-medium`}
                >
                  {profile.sex}
                </span>
              </div>
            ) : (
              <p>Sex: Unknown</p>
            )}
          </div>
        </div>

        {/* Demographics Info */}
        <div>
          <div className="flex items-center mb-2">
            <MapIcon size={18} className="text-primary mr-2" />
            <h3 className="text-sm font-medium">Demographics</h3>
          </div>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-1 text-xs">
            <p><span className="font-bold">Street:</span> {demographics?.streetaddress || 'N/A'}</p>
            <p><span className="font-bold">Barangay:</span> {demographics?.barangay || 'N/A'}</p>
            <p><span className="font-bold">City:</span> {demographics?.city || 'N/A'}</p>
            <p><span className="font-bold">Province:</span> {demographics?.province || 'N/A'}</p>
            <p><span className="font-bold">Region:</span> {demographics?.region || 'N/A'}</p>
            <p><span className="font-bold">Zipcode:</span> {demographics?.zipcode || 'N/A'}</p>
          </div>
        </div>
      </div>

  );
}

