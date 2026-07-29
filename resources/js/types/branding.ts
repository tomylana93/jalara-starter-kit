export type AuthLayoutPreset = 'simple' | 'card' | 'split';
export type AppLayoutPreset = 'sidebar' | 'header';
export type ColorThemePreset =
    | 'neutral'
    | 'blue'
    | 'emerald'
    | 'violet'
    | 'rose'
    | 'amber'
    | 'teal'
    | 'cyan'
    | 'indigo'
    | 'orange';
export type FontPreset =
    'instrument-sans' | 'system-sans' | 'system-serif' | 'system-mono';
export type BrandingIdentityMode = 'logo' | 'icon-text';
export type BrandingAsset =
    'logo' | 'logo-dark' | 'icon' | 'icon-dark' | 'auth-background';

export type Branding = {
    companyName: string;
    footerText: string | null;
    identityMode: BrandingIdentityMode;
    logoUrl: string | null;
    logoDarkUrl: string | null;
    iconUrl: string | null;
    iconDarkUrl: string | null;
    authBackgroundUrl: string | null;
    authLayout: AuthLayoutPreset;
    appLayout: AppLayoutPreset;
    colorTheme: ColorThemePreset;
    fontPreset: FontPreset;
};
