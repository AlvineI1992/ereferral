import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Info } from 'lucide-react';

type ProfileData = {
    name: string;
    email: string;
    primary_role?: string | null;
    role?: string | null;
    status?: string;
    avatar?: string;
};

type Props = {
    profile: ProfileData | null;
};

export default function Profileinfo({ profile }: Props) {
    if (!profile) {
        return <p>Loading profile...</p>;
    }

    const roleLabel = profile.primary_role || profile.role || 'No role assigned';

    return (
        <Card className="mx-2 overflow-hidden shadow-sm">
            <CardHeader className="bg-muted/20 border-b pb-4">
                <CardTitle className="flex items-center gap-2 text-base">
                    <Info size={15} />
                    User Details
                </CardTitle>
            </CardHeader>

            <CardContent className="space-y-4 p-4">
                <div className="flex items-center gap-4">
                    <Avatar className="ring-primary h-12 w-12 shadow-sm ring-1">
                        <AvatarImage src={profile.avatar || '/default-avatar.jpg'} alt={`${profile.name}'s avatar`} />
                        <AvatarFallback>{profile.name?.charAt(0).toUpperCase()}</AvatarFallback>
                    </Avatar>

                    <div className="space-y-1">
                        <h2 className="text-md font-bold">{profile.name}</h2>
                        <p className="text-muted-foreground text-sm">{profile.email}</p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Badge variant="outline">{roleLabel}</Badge>
                    <Badge variant={profile.status === 'A' ? 'default' : 'outline'}>{profile.status === 'A' ? 'Active' : 'Inactive'}</Badge>
                </div>
            </CardContent>
        </Card>
    );
}
