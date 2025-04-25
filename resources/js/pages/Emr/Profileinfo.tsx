// Profileinfo.tsx
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';

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

  return (
    <div className="flex items-center gap-4 mr-6 my-4 ml-4">
      <Avatar className="w-16 h-16">
        <AvatarImage src={profile.avatar || '/default-avatar.jpg'} />
        <AvatarFallback>{profile.emr_name?.charAt(0)}</AvatarFallback>
      </Avatar>
      <div className="ml-2">
        <h2 className="text-xl font-semibold">{profile.emr_name}</h2>
        <p className="text-gray-500 text-sm">{profile.role}</p>
      </div>
    </div>

  );
}
