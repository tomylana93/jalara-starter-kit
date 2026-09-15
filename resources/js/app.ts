import { createInertiaApp } from '@inertiajs/vue3';

import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

import { initializeTheme } from '@/composables/useAppearance';

import { initializeFlashToast } from '@/lib/flashToast';

import { createAppI18n } from '@/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    withApp(app, { page }) {
        app.use(createAppI18n(page.props.locale));
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
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
