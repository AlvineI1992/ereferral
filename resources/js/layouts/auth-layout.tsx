import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

import { useCallback } from 'react';

export default function AuthLayout({
    children,
    title,
    description,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description: string;
}) {
  

    return (
        <AuthLayoutTemplate title={title} description={description} {...props}>
            <div className="relative z-10">{children}</div>
        </AuthLayoutTemplate>
    );
}
