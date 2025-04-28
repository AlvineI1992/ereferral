// RolesInfo.tsx
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';

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
    <div className="flex items-center gap-4 mr-6 my-4 ml-4">
      <Avatar className="w-16 h-16">
        <AvatarImage src={profile.avatar || '/default-avatar.jpg'} />
        <AvatarFallback>{profile.name?.charAt(0)}</AvatarFallback>
      </Avatar>
      <div className="ml-2">
        <h2 className="text-xl font-semibold">{profile.name}</h2>
      </div>
    </div>
  );
}
