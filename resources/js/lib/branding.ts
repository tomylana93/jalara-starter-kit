import type {
    AppLayoutPreset,
    AuthLayoutPreset,
    Branding,
    ColorThemePreset,
    FontPreset,
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
] as const;

export const fontPresets: readonly FontPreset[] = [
    'instrument-sans',
    'system-sans',
    'system-serif',
    'system-mono',
] as const;

export const defaultBranding: Branding = {
    companyName: 'Laravel',
    footerText: null,
    authLayout: 'simple',
    appLayout: 'sidebar',
    colorTheme: 'neutral',
    fontPreset: 'instrument-sans',
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
    fontPreset: FontPreset,
): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.colorTheme = colorTheme;
    document.documentElement.dataset.fontPreset = fontPreset;
}

/**
 * Build the document title from the branding identity.
 */
export function brandedTitle(
    title: string | null | undefined,
    companyName: string,
): string {
    const company = companyName.trim() || defaultBranding.companyName;
    const page = (title ?? '').trim();

    return page ? `${page} - ${company}` : company;
}
