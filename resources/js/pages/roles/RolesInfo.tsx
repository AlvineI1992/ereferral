// RolesInfo.tsx
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Info } from 'lucide-react';

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

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
    <Card className='ml-2 mr-2'>
  <CardHeader>
    <div className="flex items-center space-x-1">
      <Info size={15} />
      <CardTitle className="text-sm font-semibold text-gray-700">
        Role Details
      </CardTitle>
    </div>
   
  </CardHeader>

  <CardContent>
    <div className="flex items-center space-x-4">
      <Avatar className="w-10 h-10 ring-1 ring-primary shadow-sm">
        <AvatarImage src={profile.avatar || '/default-avatar.jpg'} alt={`${profile.name}'s avatar`} />
        <AvatarFallback>
          {profile.name?.charAt(0).toUpperCase()}
        </AvatarFallback>
      </Avatar>
        <h2 className="text-md font-bold text-gray-800">{profile.name}</h2>
        <p className="text-sm text-gray-500">{profile.email}</p>
        {profile.role && (
          <span className="inline-block mt-1 px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
            {profile.role}
          </span>
        )}
    </div>
  </CardContent>  
</Card>);
}
