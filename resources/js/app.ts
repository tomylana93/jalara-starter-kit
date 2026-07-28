import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { initializeTheme } from '@/composables/useAppearance';
import AccountLayout from '@/layouts/account/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { brandedTitle, defaultBranding } from '@/lib/branding';
import { initializeFlashToast } from '@/lib/flashToast';

const fallbackName =
    import.meta.env.VITE_APP_NAME || defaultBranding.companyName;

createInertiaApp({
    title: (title) =>
        brandedTitle(
            title,
            usePage().props?.branding?.companyName ?? fallbackName,
        ),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('account/'):
                return [AppLayout, AccountLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
