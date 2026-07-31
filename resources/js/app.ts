import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { Primitive } from 'reka-ui';
import { initializeTheme } from '@/composables/useAppearance';
import AccountLayout from '@/layouts/account/Layout.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { applicationTitle, fallbackApplicationName } from '@/lib/branding';
import { initializeFlashToast } from '@/lib/flashToast';

const fallbackName = import.meta.env.VITE_APP_NAME || fallbackApplicationName;

/*
 * Configured before any component mounts, because the notification composables
 * resolve this singleton during setup. Connection options are read from the
 * VITE_REVERB_* environment. Guarded because the SSR bundle has no window and
 * must not open a socket.
 */
if (typeof window !== 'undefined') {
    configureEcho({ broadcaster: 'reverb' });
}

createInertiaApp({
    title: (title, page) =>
        applicationTitle(title, page.props.name ?? fallbackName),
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
        color: 'var(--primary)',
    },
    withApp(app) {
        /*
         * AttachmentTrigger from the current shadcn-vue registry references
         * Primitive as a global component. Registering it here keeps registry
         * files update-safe while providing the missing runtime dependency.
         */
        app.component('Primitive', Primitive);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
