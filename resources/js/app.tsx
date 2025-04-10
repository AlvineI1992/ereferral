import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import toastr from 'toastr';  // Import toastr
import 'toastr/build/toastr.min.css';  // Import toastr CSS

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize Inertia app
createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();

// Example toastr setup (if you want to customize its global settings)
toastr.options = {
    "closeButton": true, // Enable close button
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right", // Change the position to top right
    "preventDuplicates": true,
    "showDuration": "300", // Show toast for 300ms
    "hideDuration": "1000", // Hide toast for 1000ms
    "hideEasing": "linear",
    "timeOut": "5000", // Toast will disappear after 5 seconds
    "extendedTimeOut": "1000"
};


