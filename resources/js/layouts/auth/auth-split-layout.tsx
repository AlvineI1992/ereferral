import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useState, type PropsWithChildren } from 'react';

import 'swiper/css'; // Default Swiper styles
import 'swiper/css/effect-fade'; // If you're using fade effect
import 'swiper/css/navigation'; // If you're using navigation
import 'swiper/css/pagination'; // If you're using pagination
import 'swiper/css/effect-coverflow';
import { Autoplay, EffectFade,EffectCoverflow  } from 'swiper/modules'; // ✅ Import modulesimport {  } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/react'; // Import Swiper and SwiperSlide



interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { name, quote } = usePage<SharedData>().props;
    const [currentSlide, setCurrentSlide] = useState(0);

    const images = ['doh.jpg','herbosa.jpg', 'bbm.jpg',];

    const nextSlide = () => {
        setCurrentSlide((prev) => (prev + 1) % images.length);
    };

    const prevSlide = () => {
        setCurrentSlide((prev) => (prev - 1 + images.length) % images.length);
    };

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="bg-muted relative hidden h-full flex-col rounded-xl p-10 text-white lg:flex dark:border-r">
                {/* Swiper for image carousel */}
                <div className="absolute inset-0 h-full w-full">
                    <Swiper
                        modules={[Autoplay, EffectCoverflow]} // ✅ Register modules
                        loop={true} // Loop through images
                        autoplay={{ delay: 7000 }} // Autoplay every 2 seconds
                        effect="effect" // Fade effect for smooth transitions
                        className="h-full w-full rounded-xl opacity-40"
                        coverflowEffect={{
                            rotate: 50,
                            stretch: 0,
                            depth: 100,
                            modifier: 1,
                            slideShadows: true,
                          }}
                    >
                        {images.map((image, index) => (
                            <SwiperSlide key={index}>
                                <img
                                    src={image} // Image path
                                    alt="Descriptive alt text"
                                    className="h-full w-full object-cover" // Ensure the image covers the area
                                />
                            </SwiperSlide>
                        ))}
                    </Swiper>
                </div>

                <Link href={route('home')} className="relative z-40 flex items-center text-xl">
                    <div className="flex items-center space-x-4">
                        <img src="/doh-logo.png" alt="Department of Health Official Logo" className="h-24 w-24 select-none" />
                        <span className="text-2xl ">Department of Health</span>
                    </div>
                </Link>
            </div>
            
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link href={route('home')} className="relative z-20 flex items-center justify-center lg:hidden">
                        <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-muted-foreground text-sm text-balance">{description}</p>
                    </div>

                    {children}
                    {/* Watermark background image */}
                    <div className="absolute    justify-start  h-full w-full">
                            <img
                                src="/bagong_pilipinas.png"
                                alt="Watermark"
                                className="pointer-events-none h-full object-contain opacity-25"
                            />
                    </div>
                    
                </div>
                {quote && (
                    <div className="relative z-20 mt-auto">
                        <blockquote className="space-y-3">
                            <em className="text-sm text-green-800">&ldquo;{quote.message}&rdquo;</em>
                            <footer className="text-xs text-green-700">-{quote.author}</footer>
                        </blockquote>
                    </div>
                )}
            </div>
            
        </div>
    );
}
