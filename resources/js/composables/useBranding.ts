import { usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import type { Component, ComputedRef } from 'vue';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import AuthCardLayout from '@/layouts/auth/AuthCardLayout.vue';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue';
import {
    defaultBranding,
    resolvePreset,
    syncBrandingAttributes,
} from '@/lib/branding';
import type {
    AppLayoutPreset,
    AuthLayoutPreset,
    Branding,
} from '@/types/branding';

/**
 * Explicit component maps. Import paths are never built from a preset value.
 */
export const authLayouts: Record<AuthLayoutPreset, Component> = {
    simple: AuthSimpleLayout,
    card: AuthCardLayout,
    split: AuthSplitLayout,
};

export const appLayouts: Record<AppLayoutPreset, Component> = {
    sidebar: AppSidebarLayout,
    header: AppHeaderLayout,
};

export type UseBrandingReturn = {
    branding: ComputedRef<Branding>;
    authLayout: ComputedRef<Component>;
    appLayout: ComputedRef<Component>;
};

export function useBranding(): UseBrandingReturn {
    const page = usePage();

    const branding = computed<Branding>(() => ({
        ...defaultBranding,
        ...(page.props.branding ?? {}),
    }));

    watch(
        () => [branding.value.colorTheme, branding.value.fontPreset] as const,
        ([colorTheme, fontPreset]) =>
            syncBrandingAttributes(colorTheme, fontPreset),
        { immediate: true },
    );

    return {
        branding,
        authLayout: computed(() =>
            resolvePreset(
                authLayouts,
                branding.value.authLayout,
                defaultBranding.authLayout,
            ),
        ),
        appLayout: computed(() =>
            resolvePreset(
                appLayouts,
                branding.value.appLayout,
                defaultBranding.appLayout,
            ),
        ),
    };
}
