import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

import { useCallback } from 'react';

import { loadFull } from 'tsparticles';

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
    // Init callback for tsparticles
    const particlesInit = useCallback(async (engine) => {
        console.log('Particles initialized');
        await loadFull(engine);
    }, []);

    return (
        <AuthLayoutTemplate title={title} description={description} {...props}>
            
            <div className="relative z-10">{children}</div>
        </AuthLayoutTemplate>
    );
}
