// RolesInfo.tsx
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info } from 'lucide-react';

type ProfileData = {
  name: string;
  guard_name: string;
  avatar?: string;
};

type Props = {
  profile: ProfileData | null;
};

export default function Profileinfo({ profile }: Props) {
  if (!profile) {
    return <p>Loading profile...</p>;
  }

  return (
    <div className='ml-2 mr-2'>
    <div className="flex items-center space-x-1 mb-2">
      <Info size={15} />
      <h3 className="text-sm font-semibold">
        User Details
      </h3>
    </div>
  
    <div className="flex items-center space-x-4">
      <Avatar className="w-12 h-12 ring-1 ring-primary shadow-sm">
        <AvatarImage src={profile.avatar || '/default-avatar.jpg'} alt={`${profile.name}'s avatar`} />
        <AvatarFallback>
          {profile.name?.charAt(0).toUpperCase()}
        </AvatarFallback>
      </Avatar>
  
      <div className="flex flex-col">
        <h2 className="text-md font-bold ">{profile.name}</h2>
        <p className="text-sm text-gray-800">{profile.email}</p>
        {profile.role && (
          <span className="inline-block mt-1 px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full w-max">
            {profile.role}
          </span>
        )}
      </div>
    </div>
  </div>
  
);
}
