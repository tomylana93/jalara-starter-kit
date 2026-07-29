import type {
    AppLayoutPreset,
    AuthLayoutPreset,
    Branding,
    BrandingIdentityMode,
    ColorThemePreset,
    FontPairPreset,
} from '@/types/branding';

export const authLayoutPresets: readonly AuthLayoutPreset[] = [
    'simple',
    'card',
    'split',
] as const;

export const appLayoutPresets: readonly AppLayoutPreset[] = [
    'sidebar',
    'header',
] as const;

export const colorThemePresets: readonly ColorThemePreset[] = [
    'neutral',
    'blue',
    'emerald',
    'violet',
    'rose',
    'amber',
    'teal',
    'cyan',
    'indigo',
    'orange',
] as const;

export const fontPairPresets: readonly FontPairPreset[] = [
    'instrument-sans',
    'space-grotesk-inter',
    'poppins-inter',
    'montserrat-open-sans',
    'playfair-display-source-sans',
] as const;

export const brandingIdentityModes: readonly BrandingIdentityMode[] = [
    'logo',
    'icon-text',
] as const;

/**
 * Used whenever the server sends no application name.
 */
export const fallbackApplicationName = 'Laravel';

export const defaultBranding: Branding = {
    companyName: 'Laravel',
    footerText: null,
    identityMode: 'icon-text',
    logoUrl: null,
    logoDarkUrl: null,
    iconUrl: null,
    iconDarkUrl: null,
    authBackgroundUrl: null,
    authLayout: 'simple',
    appLayout: 'sidebar',
    colorTheme: 'neutral',
    fontPair: 'instrument-sans',
};

/**
 * Pick a value from an explicit preset map, falling back to the default preset
 * whenever the server sends something unknown.
 */
export function resolvePreset<Preset extends string, Value>(
    map: Record<Preset, Value>,
    preset: Preset | null | undefined,
    fallback: Preset,
): Value {
    if (preset && Object.prototype.hasOwnProperty.call(map, preset)) {
        return map[preset];
    }

    return map[fallback];
}

/**
 * Mirror the presets onto <html> so a client-side update matches what the
 * server renders on a full reload.
 */
export function syncBrandingAttributes(
    colorTheme: ColorThemePreset,
    fontPair: FontPairPreset,
): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.colorTheme = colorTheme;
    document.documentElement.dataset.fontPair = fontPair;
}

/**
 * Build the document title from the application identity.
 */
export function applicationTitle(
    title: string | null | undefined,
    applicationName: string,
): string {
    const application = applicationName.trim() || fallbackApplicationName;
    const page = (title ?? '').trim();

    return page ? `${page} - ${application}` : application;
}
