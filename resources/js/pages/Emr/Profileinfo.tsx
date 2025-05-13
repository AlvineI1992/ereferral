import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { UserCircle2Icon } from 'lucide-react';

type ProfileData = {
    emr_name: string;
    role: string;
    email: string;
    phone: string;
    location: string;
    avatar?: string;
};

type Props = {
    profile: ProfileData | null;
};

export default function Profileinfo({ profile }: Props) {
    if (!profile) {
        return <p>Loading profile...</p>;
    }

    const initial = profile.emr_name?.charAt(0).toUpperCase() || '?';

    return (
      <div className="space-y-6">
      <div className="p-3 ">
        {/* Header */}
        <div className="flex items-center mb-4">
          <UserCircle2Icon size={24} className="text-primary mr-2" />
          <h2 className="text-lg">Provider Profile</h2>
        </div>

        {/* Content */}
        <div className="flex flex-col sm:flex-row sm:items-start gap-5">
          {/* Avatar */}
          <Avatar className="w-12 h-12 ring-1 ring-primary shadow-sm">
            <AvatarImage
              src={profile?.avatar || '/default-avatar.jpg'}
              alt={`${profile.emr_name}'s avatar`}
            />
            <AvatarFallback>{initial}</AvatarFallback>
          </Avatar>

          {/* Profile Info */}
          <div className="space-y-1 text-sm ">
            <p className="text-lg">{profile.emr_name || 'No Name'}</p>
           
          </div>
        </div>
      </div>
    </div>
    );
}
