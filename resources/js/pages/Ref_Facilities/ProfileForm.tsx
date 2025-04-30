import { usePage } from '@inertiajs/react';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';
import ProfileLayout from './ProfileLayout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: '/settings/appearance',
    },
];

export default function ProfileForm() {
    const { props } = usePage();
    const { id } = props;  // Assuming the id is passed in the page props

    return (
        <ProfileLayout id={id}>
            <div className="space-y-6">
                <HeadingSmall title="Profile" description="" />
            </div>
        </ProfileLayout>
    );
}
