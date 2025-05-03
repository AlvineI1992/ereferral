import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

import { useCallback } from 'react';
import Particles from 'react-tsparticles';
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
            <Particles
                id="tsparticles"
                init={particlesInit}
                className="absolute inset-0 z-10"
                options={{
                    fullScreen: false,
                    particles: {
                        number: {
                            value: 60,
                            density: { enable: true, area: 800 },
                        },
                        color: { value: '#90EE90' },
                        shape: { type: 'circle' },
                        opacity: { value: 0.3 },
                        size: { value: 3 },
                        move: {
                            enable: true,
                            speed: 1,
                            direction: 'none',
                            outModes: { default: 'bounce' },
                        },
                    },
                }}
            />
            <div className="relative z-10">{children}</div>
        </AuthLayoutTemplate>
    );
}
