import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CalendarDays, Home, MapPinned, Mars, UserRound, Venus } from 'lucide-react';

type ProfileData = {
    fname: string;
    mname: string;
    lname?: string;
    dob?: string;
    age?: string;
    sex?: string;
    avatar?: string | null;
};

type ProfileDemographics = {
    region?: string;
    province?: string;
    city?: string;
    barangay?: string;
    street?: string;
    streetaddress?: string;
    zipcode?: string;
};

type Props = {
    profile: ProfileData | null;
    demographics: ProfileDemographics | null;
};

export default function PatientInfo({ profile, demographics }: Props) {
    const fullName = [profile?.fname, profile?.mname, profile?.lname].filter(Boolean).join(' ');
    const initials =
        `${profile?.fname?.charAt(0) ?? ''}${profile?.lname?.charAt(0) ?? ''}`.trim().toUpperCase() || '?';
    const address = [demographics?.streetaddress || demographics?.street, demographics?.barangay, demographics?.city, demographics?.province]
        .filter(Boolean)
        .join(', ');

    return (
        <Card className="border-slate-200/80 bg-white/90 shadow-sm">
            <CardHeader className="border-b border-slate-100 pb-5">
                <div className="flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                        <UserRound className="size-5" />
                    </div>
                    <div>
                        <CardTitle className="text-lg">Patient Profile</CardTitle>
                        <p className="mt-1 text-sm text-slate-500">Identity and location details attached to this referral.</p>
                    </div>
                </div>
            </CardHeader>

            <CardContent className="space-y-5 pt-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <Avatar className="h-20 w-20 ring-2 ring-cyan-100 shadow-sm">
                        <AvatarImage src={profile?.avatar || '/default-avatar.jpg'} alt={fullName || 'Patient'} />
                        <AvatarFallback>{initials}</AvatarFallback>
                    </Avatar>

                    <div className="min-w-0 space-y-3">
                        <div>
                            <p className="text-xl font-semibold tracking-tight text-slate-950">{fullName || 'No name available'}</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                <Badge variant="outline" className="rounded-full border-slate-200 bg-slate-50">
                                    Age {profile?.age || 'N/A'}
                                </Badge>
                                <Badge variant="outline" className="rounded-full border-slate-200 bg-slate-50">
                                    <CalendarDays className="size-3" />
                                    {profile?.dob || 'Birthdate unavailable'}
                                </Badge>
                                {profile?.sex && (
                                    <Badge
                                        variant="outline"
                                        className={
                                            profile.sex === 'Male'
                                                ? 'rounded-full border-blue-200 bg-blue-50 text-blue-700'
                                                : 'rounded-full border-pink-200 bg-pink-50 text-pink-700'
                                        }
                                    >
                                        {profile.sex === 'Male' ? <Mars className="size-3" /> : <Venus className="size-3" />}
                                        {profile.sex}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                        <div className="flex items-center gap-2">
                            <MapPinned className="size-4 text-emerald-700" />
                            <p className="text-sm font-semibold text-slate-900">Demographics</p>
                        </div>
                        <div className="mt-3 space-y-2 text-sm text-slate-600">
                            <p><span className="font-medium text-slate-900">Region:</span> {demographics?.region || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">Province:</span> {demographics?.province || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">City:</span> {demographics?.city || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">Barangay:</span> {demographics?.barangay || 'N/A'}</p>
                            <p><span className="font-medium text-slate-900">Zipcode:</span> {demographics?.zipcode || 'N/A'}</p>
                        </div>
                    </div>

                    <div className="rounded-[1.5rem] border border-slate-200/80 bg-slate-50/70 p-4">
                        <div className="flex items-center gap-2">
                            <Home className="size-4 text-cyan-700" />
                            <p className="text-sm font-semibold text-slate-900">Address Summary</p>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-slate-600">{address || 'No address details available.'}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
